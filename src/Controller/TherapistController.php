<?php

namespace App\Controller;

use App\Entity\Therapist;
use App\Form\TherapistType;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/therapist')]
#[IsGranted('ROLE_ADMIN')]
final class TherapistController extends AbstractController
{
    #[Route(name: 'app_therapist_index', methods: ['GET'])]
    public function index(TherapistRepository $therapistRepository): Response
    {
        return $this->render('therapist/index.html.twig', [
            'therapists' => $therapistRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_therapist_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $therapist = new Therapist();
        $form = $this->createForm(TherapistType::class, $therapist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($therapist);
            $entityManager->flush();

            return $this->redirectToRoute('app_therapist_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('therapist/new.html.twig', [
            'therapist' => $therapist,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_therapist_show', methods: ['GET'])]
    public function show(Therapist $therapist): Response
    {
        return $this->render('therapist/show.html.twig', [
            'therapist' => $therapist,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_therapist_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Therapist $therapist, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TherapistType::class, $therapist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_therapist_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('therapist/edit.html.twig', [
            'therapist' => $therapist,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_therapist_delete', methods: ['POST'])]
    public function delete(Request $request, Therapist $therapist, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$therapist->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($therapist);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_therapist_index', [], Response::HTTP_SEE_OTHER);
    }
}
