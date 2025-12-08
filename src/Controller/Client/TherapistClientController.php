<?php

namespace App\Controller\Client;

use App\Repository\TherapistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/therapists', name: 'client_therapist_')]
#[IsGranted('ROLE_USER')]
class TherapistClientController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, TherapistRepository $therapistRepository): Response
    {
        $searchName = $request->query->get('name', '');
        $searchSpecialization = $request->query->get('specialization', '');

        $queryBuilder = $therapistRepository->createQueryBuilder('t');

        if ($searchName) {
            $queryBuilder->andWhere('t.name LIKE :name')
                         ->setParameter('name', '%'.$searchName.'%');
        }

        if ($searchSpecialization) {
            $queryBuilder->andWhere('t.specialization LIKE :spec')
                         ->setParameter('spec', '%'.$searchSpecialization.'%');
        }

        $therapists = $queryBuilder->getQuery()->getResult();

        return $this->render('client/therapist/index.html.twig', [
            'therapists' => $therapists,
            'search_name' => $searchName,
            'search_specialization' => $searchSpecialization,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, TherapistRepository $therapistRepository): Response
    {
        $therapist = $therapistRepository->find($id);

        if (!$therapist) {
            throw $this->createNotFoundException('Therapist not found');
        }

        return $this->render('client/therapist/show.html.twig', [
            'therapist' => $therapist,
        ]);
    }
}
