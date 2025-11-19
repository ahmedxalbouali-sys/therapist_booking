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

#[Route('/admin/appointments')]
#[IsGranted('ROLE_ADMIN')] // ensure only admins can access this controller
final class AdminAppointmentController extends AbstractController
{
#[Route(name: 'admin_appointment_index', methods: ['GET'])]
public function index(Request $request, AppointmentRepository $appointmentRepository): Response
{
    $qb = $appointmentRepository->createQueryBuilder('a')
        ->join('a.client', 'c')
        ->join('a.therapist', 't');

    if ($clientName = $request->query->get('client')) {
        $qb->andWhere('c.firstName LIKE :client OR c.lastName LIKE :client')
           ->setParameter('client', '%'.$clientName.'%');
    }

    if ($therapistName = $request->query->get('therapist')) {
        $qb->andWhere('t.name LIKE :therapist')
           ->setParameter('therapist', '%'.$therapistName.'%');
    }

    if ($date = $request->query->get('date')) {
        $qb->andWhere('DATE(a.startAt) = :date')
           ->setParameter('date', $date);
    }

    $appointments = $qb->getQuery()->getResult();

    return $this->render('admin_appointment/index.html.twig', [
        'appointments' => $appointments,
    ]);
}


    // Show one appointment (admin)
    #[Route('/{id}', name: 'admin_appointment_show', methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        return $this->render('admin_appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    // Edit appointment (admin)
    #[Route('/{id}/edit', name: 'admin_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // save changes
            $this->addFlash('success', 'Appointment updated successfully.');

            return $this->redirectToRoute('admin_appointment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    // Delete appointment (admin)
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
