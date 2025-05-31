<?php

// src/Controller/BatimentController.php

namespace App\Controller;

use App\Entity\Batiment;
use App\Form\BatimentType;
use App\Repository\BatimentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\HistoriqueLoggerService;


#[Route('/batiment')]
class BatimentController extends AbstractController
{
    #[Route('/', name: 'app_batiment_index', methods: ['GET'])]
    public function index(BatimentRepository $batimentRepository): Response
    {
        return $this->render('batiment/index.html.twig', [
            'batiments' => $batimentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_batiment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $batiment = new Batiment();
        $form = $this->createForm(BatimentType::class, $batiment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($batiment);
            $em->flush();
            return $this->redirectToRoute('app_batiment_index');
        }

        return $this->render('batiment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/batiment/{id}', name: 'app_batiment_show', methods: ['GET'])]
    public function show(Batiment $batiment): Response
    {
        $localisations = [];

        foreach ($batiment->getLocalisations() as $localisation) {
            $localisations[] = [
                'latitude' => $localisation->getLatitude(),
                'longitude' => $localisation->getLongitude(),
                'nomBatiment' => $localisation->getNomBatiment(),
                'salle' => $localisation->getSalle(),
        ];
    }

    return $this->render('batiment/show.html.twig', [
        'batiment' => $batiment,
        'localisations' => $localisations,
    ]);
}

    #[Route('/{id}/edit', name: 'app_batiment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Batiment $batiment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BatimentType::class, $batiment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_batiment_index');
        }

        return $this->render('batiment/edit.html.twig', [
            'batiment' => $batiment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_batiment_delete', methods: ['POST'])]
    public function delete(Request $request, Batiment $batiment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$batiment->getId(), $request->request->get('_token'))) {
            $em->remove($batiment);
            $em->flush();
        }

        return $this->redirectToRoute('app_batiment_index');
    }
}
