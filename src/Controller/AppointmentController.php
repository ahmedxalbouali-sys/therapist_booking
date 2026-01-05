<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/appointment')]
#[IsGranted('ROLE_USER')]
final class AppointmentController extends AbstractController
{
    private function updateAppointmentStatus(Appointment $appointment): void
    {
        $now = new \DateTime();
        $endAt = clone $appointment->getStartAt();
        $endAt->modify('+1 hour');

        if ($now < $appointment->getStartAt()) {
            $appointment->setStatus('scheduled');
        } elseif ($now >= $appointment->getStartAt() && $now < $endAt) {
            $appointment->setStatus('in_progress');
        } else {
            $appointment->setStatus('completed');
        }
    }

    #[Route(name: 'app_appointment_index', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {   
        $user = $this->getUser();
        $appointments = $appointmentRepository->findBy(['client' => $user]);

        foreach ($appointments as $appointment) {
            $this->updateAppointmentStatus($appointment);
        }

        return $this->render('appointment/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }
    #[Route('/{id}/show', name: 'app_appointment_show', methods: ['GET'])]
public function show(Appointment $appointment): Response
{
    $user = $this->getUser();
    if ($appointment->getClient() !== $user) {
        throw $this->createAccessDeniedException();
    }

    $this->updateAppointmentStatus($appointment);

    return $this->render('appointment/show.html.twig', [
        'appointment' => $appointment,
    ]);
}
    #[Route('/new', name: 'app_appointment_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, AppointmentRepository $appointmentRepository): Response
{
    $appointment = new Appointment();
    $appointment->setClient($this->getUser());

    $form = $this->createForm(AppointmentType::class, $appointment, [
        'is_admin' => false,
        'current_user' => $this->getUser(),
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // ------------------ New: prevent past booking ------------------
        $now = new \DateTime();
        if ($appointment->getStartAt() < $now) {
            $this->addFlash('error', 'You cannot book an appointment in the past.');
            return $this->redirectToRoute('app_appointment_new');
        }
        // ----------------------------------------------------------------

        $appointmentEnd = (clone $appointment->getStartAt())->modify('+1 hour');

        $existing = $appointmentRepository->createQueryBuilder('a')
            ->where('a.therapist = :therapist')
            ->andWhere('a.startAt < :newEnd')
            ->andWhere('DATE_ADD(a.startAt, 1, \'hour\') > :newStart')
            ->setParameter('therapist', $appointment->getTherapist())
            ->setParameter('newStart', $appointment->getStartAt())
            ->setParameter('newEnd', $appointmentEnd)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing) {
            $this->addFlash('error', 'This therapist is already booked at this time. Please choose another slot.');
            return $this->redirectToRoute('app_appointment_new');
        }

        $entityManager->persist($appointment);
        $entityManager->flush();

        $this->addFlash('success', 'Your appointment has been booked successfully.');
        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('appointment/new.html.twig', [
        'appointment' => $appointment,
        'form' => $form,
    ]);
}


    #[Route('/{id}/edit', name: 'app_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if ($appointment->getClient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'is_admin' => false,
            'current_user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $appointmentEnd = (clone $appointment->getStartAt())->modify('+1 hour');

            $existing = $appointmentRepository->createQueryBuilder('a')
                ->where('a.therapist = :therapist')
                ->andWhere('a.id != :id')
                ->andWhere('a.startAt < :newEnd')
                ->andWhere('DATE_ADD(a.startAt, 1, \'hour\') > :newStart')
                ->setParameter('therapist', $appointment->getTherapist())
                ->setParameter('newStart', $appointment->getStartAt())
                ->setParameter('newEnd', $appointmentEnd)
                ->setParameter('id', $appointment->getId())
                ->getQuery()
                ->getOneOrNullResult();

            if ($existing) {
                $this->addFlash('error', 'This therapist is already booked at this time. Please choose another slot.');
                return $this->redirectToRoute('app_appointment_edit', ['id' => $appointment->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Appointment updated successfully.');
            return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($appointment->getClient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$appointment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Appointment deleted successfully.');
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }
}
