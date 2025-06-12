<?php

namespace App\Controller;

use App\Entity\Localisation;
use App\Form\LocalisationType;
use App\Service\HistoriqueLoggerService;
use App\Repository\LocalisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/localisation')]
class LocalisationController extends AbstractController
{
    #[Route('/', name: 'app_localisation_index', methods: ['GET'])]
    public function index(LocalisationRepository $localisationRepository): Response
    {
        return $this->render('localisation/index.html.twig', [
            'localisations' => $localisationRepository->findAll(),
        ]);
    }

    #[Route('/localisation/new', name: 'app_localisation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $localisation = new Localisation();
        $form = $this->createForm(LocalisationType::class, $localisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($localisation);
            $entityManager->flush();

            return $this->redirectToRoute('app_localisation_index');
        }

        return $this->render('localisation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_localisation_show', methods: ['GET'])]
    public function show(Localisation $localisation): Response
    {
        return $this->render('localisation/show.html.twig', [
            'localisation' => $localisation,
        ]);
    }

   #[Route('/{id}/edit', name: 'app_localisation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Localisation $localisation, EntityManagerInterface $entityManager, HistoriqueLoggerService $logger, Security $security): Response
    {
        $form = $this->createForm(LocalisationType::class, $localisation);
        $form->handleRequest($request);

        $user = $security->getUser();
        $oldData = [
            'nomBatiment' => $localisation->getNomBatiment(),
            'etage' => $localisation->getEtage(),
            'salle' => $localisation->getSalle(),
            'codeLocal' => $localisation->getCodeLocal(),
            'responsable' => $localisation->getResponsable(),
            'latitude' => $localisation->getLatitude(),
            'longitude' => $localisation->getLongitude(),
        ];

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            if (is_array($oldData)) {
                $logger->logAction( // Utilisons logAction directement pour éviter confusion avec le listener
                    'MODIFICATION_Localisation',
                    "Modification de Localisation (ID: {$localisation->getId()})",
                    $user,
                    $localisation->getId(),
                    ['old_data' => $oldData, 'new_data' => get_object_vars($localisation)]
                );
            } else {
                $this->addFlash('error', 'Erreur lors de la journalisation des modifications.');
            }
            return $this->redirectToRoute('app_localisation_index');
        }

        return $this->render('localisation/edit.html.twig', [
            'localisation' => $localisation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_localisation_delete', methods: ['POST'])]
    public function delete(Request $request, Localisation $localisation, EntityManagerInterface $entityManager, HistoriqueLoggerService $logger, Security $security): Response
    {
        if ($this->isCsrfTokenValid('delete'.$localisation->getId(), $request->request->get('_token'))) {
            $user = $security->getUser();
            $logger->logAction('SUPPRESSION', 'Suppression de localisation', $user, $localisation->getId(), [
                'nomBatiment' => $localisation->getNomBatiment(),
                'etage' => $localisation->getEtage(),
                'salle' => $localisation->getSalle(),
                'codeLocal' => $localisation->getCodeLocal(),
                'responsable' => $localisation->getResponsable(),
                'latitude' => $localisation->getLatitude(),
                'longitude' => $localisation->getLongitude(),
            ]);

            $entityManager->remove($localisation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_localisation_index', [], Response::HTTP_SEE_OTHER);
    }
}