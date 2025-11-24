<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/appointments')]
#[IsGranted('ROLE_ADMIN')]
final class AdminAppointmentController extends AbstractController
{
    #[Route(name: 'admin_appointment_index', methods: ['GET'])]
    public function index(Request $request, AppointmentRepository $appointmentRepository): Response
    {
        $qb = $appointmentRepository->createQueryBuilder('a')
            ->join('a.client', 'c')
            ->join('a.therapist', 't');

        // Filter by client name
        if ($clientName = $request->query->get('client')) {
            $qb->andWhere('c.firstName LIKE :client OR c.lastName LIKE :client')
               ->setParameter('client', '%'.$clientName.'%');
        }

        // Filter by therapist name
        if ($therapistName = $request->query->get('therapist')) {
            $qb->andWhere('t.name LIKE :therapist')
               ->setParameter('therapist', '%'.$therapistName.'%');
        }

        // Filter by date (fixed)
        if ($date = $request->query->get('date')) {
            $start = new \DateTime($date . ' 00:00:00');
            $end = new \DateTime($date . ' 23:59:59');
            $qb->andWhere('a.startAt BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }

        $appointments = $qb->getQuery()->getResult();

        return $this->render('admin_appointment/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/{id}', name: 'admin_appointment_show', methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        return $this->render('admin_appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager, AppointmentRepository $appointmentRepository): Response
    {
        $form = $this->createForm(AppointmentType::class, $appointment, [
            'is_admin' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Check for overlapping appointments
            $appointmentEnd = (clone $appointment->getStartAt())->modify('+1 hour');

            $existingAppointments = $appointmentRepository->createQueryBuilder('a')
                ->where('a.therapist = :therapist')
                ->andWhere('a.id != :id')
                ->andWhere('a.startAt < :newEnd')
                ->andWhere('DATE_ADD(a.startAt, 0, \'second\') + 3600 > :newStart') // PHP-safe comparison
                ->setParameter('therapist', $appointment->getTherapist())
                ->setParameter('newStart', $appointment->getStartAt())
                ->setParameter('newEnd', $appointmentEnd)
                ->setParameter('id', $appointment->getId())
                ->getQuery()
                ->getResult();

            if (count($existingAppointments) > 0) {
                $this->addFlash('error', 'This therapist is already booked at this time. Please choose another slot.');
                return $this->redirectToRoute('admin_appointment_edit', ['id' => $appointment->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Appointment updated successfully.');

            return $this->redirectToRoute('admin_appointment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/new', name: 'admin_appointment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AppointmentRepository $appointmentRepository): Response
    {
        $appointment = new Appointment();

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'is_admin' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointmentEnd = (clone $appointment->getStartAt())->modify('+1 hour');

            $existingAppointments = $appointmentRepository->createQueryBuilder('a')
                ->where('a.therapist = :therapist')
                ->andWhere('a.startAt < :newEnd')
                ->andWhere('DATE_ADD(a.startAt, 0, \'second\') + 3600 > :newStart')
                ->setParameter('therapist', $appointment->getTherapist())
                ->setParameter('newStart', $appointment->getStartAt())
                ->setParameter('newEnd', $appointmentEnd)
                ->getQuery()
                ->getResult();

            if (count($existingAppointments) > 0) {
                $this->addFlash('error', 'This therapist is already booked at this time. Please choose another slot.');
                return $this->redirectToRoute('admin_appointment_new');
            }

            $entityManager->persist($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Appointment created successfully.');

            return $this->redirectToRoute('admin_appointment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_appointment/new.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$appointment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Appointment deleted successfully.');
        }

        return $this->redirectToRoute('admin_appointment_index', [], Response::HTTP_SEE_OTHER);
    }
}
