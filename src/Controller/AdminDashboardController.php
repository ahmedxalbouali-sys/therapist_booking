<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Therapist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'app_admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('/', name: '')] // GET /admin
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Fetch all appointments
        $appointments = $em->getRepository(Appointment::class)->findAll();

        // Total appointments
        $totalAppointments = count($appointments);

        // Appointments today
        $today = (new \DateTime())->format('Y-m-d');
        $appointmentsToday = count(array_filter($appointments, fn($a) => $a->getStartAt()->format('Y-m-d') === $today));

        // Pending appointments (scheduled)
        $pendingAppointments = count(array_filter($appointments, fn($a) => $a->getStatus() === 'scheduled'));

        // Appointment status breakdown
        $statusLabels = ['Scheduled', 'In Progress', 'Completed'];
        $statusData = [
            count(array_filter($appointments, fn($a) => $a->getStatus() === 'scheduled')),
            count(array_filter($appointments, fn($a) => $a->getStatus() === 'in_progress')),
            count(array_filter($appointments, fn($a) => $a->getStatus() === 'completed')),
        ];

        // Appointments per therapist
        $therapists = $em->getRepository(Therapist::class)->findAll();
        $therapistLabels = [];
        $appointmentsPerTherapist = [];
        foreach ($therapists as $therapist) {
            $therapistLabels[] = $therapist->getName();
            $appointmentsPerTherapist[] = count($therapist->getAppointments());
        }

        return $this->render('admin_dashboard/index.html.twig', [
    'totalAppointments' => $totalAppointments,
    'appointmentsToday' => $appointmentsToday,
    'pendingAppointments' => $pendingAppointments,
    'appointmentStatusLabels' => $statusLabels,
    'appointmentStatusData' => $statusData,
    'therapistLabels' => $therapistLabels,
    'appointmentsPerTherapist' => $appointmentsPerTherapist,
]);
    }
}
