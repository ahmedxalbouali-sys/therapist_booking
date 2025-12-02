<?php
// src/Controller/TherapistController.php
namespace App\Controller;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/therapist')]
#[IsGranted('ROLE_THERAPIST')]
class TherapistController extends AbstractController
{
    #[Route('/appointments', name: 'therapist_appointment_index')]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        $therapist = $this->getUser()->getTherapist();

        // Only fetch appointments assigned to this therapist
        $appointments = $appointmentRepository->findBy(['therapist' => $therapist]);

        return $this->render('therapist/appointments.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/appointments/{id}/status', name: 'therapist_appointment_update_status', methods: ['POST'])]
    public function updateStatus(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $therapist = $this->getUser()->getTherapist();

        // Security: only allow updating if this appointment belongs to the logged-in therapist
        if ($appointment->getTherapist() !== $therapist) {
            throw $this->createAccessDeniedException('You cannot update this appointment.');
        }

        $newStatus = $request->request->get('status');

        if (in_array($newStatus, ['scheduled', 'in_progress', 'completed'])) {
            $appointment->setStatus($newStatus);
            $em->flush();
            $this->addFlash('success', 'Appointment status updated successfully.');
        } else {
            $this->addFlash('error', 'Invalid status value.');
        }

        return $this->redirectToRoute('therapist_appointment_index');
    }
}
