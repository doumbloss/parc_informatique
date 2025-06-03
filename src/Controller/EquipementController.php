<?php

namespace App\Controller;

use App\Entity\Equipement;
use App\Entity\Panne;
use App\Form\EquipementType;
use App\Form\EquipementFiltreType;
use App\Form\SearchType;
use App\Service\HistoriqueLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipement')]
class EquipementController extends AbstractController
{
    private EntityManagerInterface $em;
    private HistoriqueLoggerService $historiqueLoggerService;

    public function __construct(EntityManagerInterface $em, HistoriqueLoggerService $historiqueLoggerService)
    {
        $this->em = $em;
        $this->historiqueLoggerService = $historiqueLoggerService;
    }

    private function getEquipementAlerts(): int
    {
        // Comptez les équipements en panne ou nécessitant une maintenance
        return $this->em->getRepository(Equipement::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etat IN (:etats)')
            ->setParameter('etats', ['en_panne', 'maintenance_requise', 'obsolete'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getPanneAlerts(): int
    {
        // Comptez les pannes non résolues (ajustez selon votre entité Panne)
        $panneRepo = $this->em->getRepository(Panne::class);
        if (!$panneRepo) {
            return 0; // Si l'entité Panne n'existe pas encore
        }
        
        return $panneRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.statut != :statut OR p.statut IS NULL')
            ->setParameter('statut', 'resolu')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getGlobalVariables(): array
    {
        return [
            'equipement_alerts' => $this->getEquipementAlerts(),
            'panne_alerts' => $this->getPanneAlerts(),
        ];
    }

    #[Route('/', name: 'app_equipement_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $queryBuilder = $this->em->getRepository(Equipement::class)->createQueryBuilder('e');

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (!empty($data['nom'])) {
                $queryBuilder->andWhere('e.nom LIKE :nom')
                    ->setParameter('nom', '%' . $data['nom'] . '%');
            }

            if (!empty($data['type'])) {
                $queryBuilder->andWhere('e.type = :type')
                    ->setParameter('type', $data['type']);
            }

            if (!empty($data['etat'])) {
                $queryBuilder->andWhere('e.etat = :etat')
                    ->setParameter('etat', $data['etat']);
            }

            if (!empty($data['localisation'])) {
                $queryBuilder->andWhere('e.localisation = :localisation')
                    ->setParameter('localisation', $data['localisation']);
            }
        }

        $equipements = $queryBuilder->getQuery()->getResult();

        return $this->render('equipement/index.html.twig', array_merge([
            'form' => $form->createView(),
            'equipements' => $equipements,
        ], $this->getGlobalVariables()));
    }

    #[Route('/new', name: 'app_equipement_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $equipement = new Equipement();
        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($equipement);
            $this->em->flush();

            $nouvelleValeur = json_encode([
                'nom' => $equipement->getNom(),
                'description' => $equipement->getDescription(),
            ]);

            $this->historiqueLoggerService->log(
                'ajout',
                $equipement,
                null,
                $nouvelleValeur,
                'Nouvel équipement ajouté'
            );

            return $this->redirectToRoute('app_equipement_index');
        }

        return $this->render('equipement/new.html.twig', array_merge([
            'equipement' => $equipement,
            'form' => $form->createView(),
        ], $this->getGlobalVariables()));
    }

    #[Route('/{id}', name: 'app_equipement_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Equipement $equipement): Response
    {
        return $this->render('equipement/show.html.twig', array_merge([
            'equipement' => $equipement,
        ], $this->getGlobalVariables()));
    }

    #[Route('/{id}/edit', name: 'app_equipement_edit', methods: ['GET', 'POST'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Equipement $equipement): Response
    {
        $ancienneValeur = json_encode([
            'nom' => $equipement->getNom(),
            'description' => $equipement->getDescription(),
        ]);

        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $nouvelleValeur = json_encode([
                'nom' => $equipement->getNom(),
                'description' => $equipement->getDescription(),
            ]);

            $this->historiqueLoggerService->log(
                'modification',
                $equipement,
                $ancienneValeur,
                $nouvelleValeur,
                'Équipement modifié'
            );

            return $this->redirectToRoute('app_equipement_index');
        }

        return $this->render('equipement/edit.html.twig', array_merge([
            'equipement' => $equipement,
            'form' => $form,
        ], $this->getGlobalVariables()));
    }

    #[Route('/{id}', name: 'app_equipement_delete', methods: ['POST'])]
    public function delete(Request $request, Equipement $equipement): Response
    {
        if ($this->isCsrfTokenValid('delete' . $equipement->getId(), $request->request->get('_token'))) {
            $ancienneValeur = json_encode([
                'nom' => $equipement->getNom(),
                'description' => $equipement->getDescription(),
            ]);

            $this->historiqueLoggerService->log(
                'suppression',
                $equipement,
                $ancienneValeur,
                null,
                'Équipement supprimé'
            );

            $this->em->remove($equipement);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_equipement_index');
    }

    #[Route('/{id}/update', name: 'equipement_update', requirements: ['id' => '\d+'])]
    public function updateEquipement(int $id): Response
    {
        $equipement = $this->em->getRepository(Equipement::class)->find($id);

        if (!$equipement) {
            throw $this->createNotFoundException('Équipement non trouvé.');
        }

        $ancienneValeur = $equipement->getNom();
        $nouvelleValeur = 'Nouveau Nom';
        $equipement->setNom($nouvelleValeur);

        $this->em->flush();

        $this->historiqueLoggerService->log(
            'modification',
            $equipement,
            $ancienneValeur,
            $nouvelleValeur,
            'Changement manuel du nom de l\'équipement'
        );

        return new Response('Équipement mis à jour et historique enregistré.');
    }

    #[Route('/equipements/mois/{mois}', name: 'app_equipements_par_mois')]
    public function parMois(int $mois, int $annee = 2025): Response
    {
        $equipements = $this->em->getRepository(Equipement::class)
            ->createQueryBuilder('e')
            ->where('MONTH(e.dateAchat) = :mois')
            ->andWhere('YEAR(e.dateAchat) = :annee')
            ->setParameter('mois', $mois)
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getResult();

        return $this->render('equipement/liste_mois.html.twig', array_merge([
            'equipements' => $equipements,
            'mois' => $mois,
        ], $this->getGlobalVariables()));
    }

    // Route pour l'API des notifications (pour les mises à jour AJAX)
    #[Route('/api/notification-counts', name: 'app_notification_counts', methods: ['GET'])]
    public function getNotificationCounts(): JsonResponse
    {
        return new JsonResponse($this->getGlobalVariables());
    }
}