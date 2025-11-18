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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


#[Route('/therapist')]
#[IsGranted('ROLE_ADMIN')]
final class TherapistController extends AbstractController
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

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
            // Handle photo upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('therapist_photos_dir'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle upload error
                    $this->addFlash('error', 'Failed to upload image.');
                }

                $therapist->setPhoto($newFilename);
            }

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
            // Handle photo upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('therapist_photos_dir'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }

                $therapist->setPhoto($newFilename);
            }

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
        if ($this->isCsrfTokenValid('delete'.$therapist->getId(), $request->request->get('_token'))) {
            $entityManager->remove($therapist);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_therapist_index', [], Response::HTTP_SEE_OTHER);
    }
}
