<?php

namespace App\Controller\Admin;

use App\Entity\Therapist;
use App\Form\TherapistType;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/admin/therapists', name: 'admin_therapist_')]
#[IsGranted('ROLE_ADMIN')]
final class TherapistController extends AbstractController
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(TherapistRepository $therapistRepository): Response
    {
        return $this->render('admin/therapist/index.html.twig', [
            'therapists' => $therapistRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $therapist = new Therapist();
        $form = $this->createForm(TherapistType::class, $therapist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $entityManager->persist($therapist);
            $entityManager->flush();

            return $this->redirectToRoute('admin_therapist_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/therapist/new.html.twig', [
            'therapist' => $therapist,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Therapist $therapist): Response
    {
        return $this->render('admin/therapist/show.html.twig', [
            'therapist' => $therapist,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Therapist $therapist, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TherapistType::class, $therapist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            return $this->redirectToRoute('admin_therapist_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/therapist/edit.html.twig', [
            'therapist' => $therapist,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Therapist $therapist, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$therapist->getId(), $request->request->get('_token'))) {
            $entityManager->remove($therapist);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_therapist_index', [], Response::HTTP_SEE_OTHER);
    }
}
