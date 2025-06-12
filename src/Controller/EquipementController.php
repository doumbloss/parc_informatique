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
use App\Form\EquipementDeleteType; // Ajouter cette ligne si absente

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
            ->where('e.statut IN (:statut)')
            ->setParameter('statut', ['en_panne', 'maintenance_requise', 'obsolete'])
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
                $queryBuilder->andWhere('e.categorie = :type') // Ajusté de 'type' à 'categorie' selon l'entité
                    ->setParameter('type', $data['type']);
            }

            if (!empty($data['statut'])) {
                $queryBuilder->andWhere('e.statut = :statut')
                    ->setParameter('statut', $data['statut']);
            }

            if ($data['localisation']) {
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
    

   #[Route('/new', name: 'app_equipement_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $equipement = new Equipement();
        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->em->persist($equipement);
                $this->em->flush();

                // Préparer les données pour logAction
                $nouvelleValeur = json_encode([
                    'nom' => $equipement->getNom(),
                    'codeInventaire' => $equipement->getCodeInventaire(),
                ]);

                // Appeler logAction avec les bons paramètres
                $this->historiqueLoggerService->logAction(
                    'ajout_equipement', // Action
                    'Nouvel équipement ajouté', // Description
                    null, // User (null si non authentifié, sinon récupérez l'utilisateur courant)
                    $equipement->getId(), // targetId (ID de l'équipement)
                    ['nouvelle_valeur' => $nouvelleValeur] // Données supplémentaires
                );

                $this->addFlash('success', 'L\'équipement a été créé avec succès.');
                return $this->redirectToRoute('app_equipement_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de la création de l\'équipement : ' . $e->getMessage());
                return $this->render('equipement/new.html.twig', array_merge([
                    'equipement' => $equipement,
                    'form' => $form->createView(),
                ], $this->getGlobalVariables()));
            }
        }

        return $this->render('equipement/new.html.twig', array_merge([
            'equipement' => $equipement,
            'form' => $form->createView(),
        ], $this->getGlobalVariables()));
    }

    #[Route('/{id}', name: 'app_equipement_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Equipement $equipement): Response
    {
        // Créer le formulaire de suppression
        $deleteForm = $this->createForm(EquipementDeleteType::class, $equipement, [
            'action' => $this->generateUrl('app_equipement_delete', ['id' => $equipement->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('equipement/show.html.twig', array_merge([
            'equipement' => $equipement,
            'delete_form' => $deleteForm->createView(), // Passer le formulaire au template
        ], $this->getGlobalVariables()));
    }
 
#[Route('/{id}/edit', name: 'app_equipement_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Equipement $equipement): Response
{
    $ancienneValeur = json_encode([
        'nom' => $equipement->getNom(),
    ]);

    $form = $this->createForm(EquipementType::class, $equipement);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $this->em->flush();

        $nouvelleValeur = json_encode([
            'nom' => $equipement->getNom(),
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
        public function delete(Request $request, Equipement $equipement, EntityManagerInterface $em): Response
        {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('delete' . $equipement->getId(), $token)) {
                $this->addFlash('danger', 'Token CSRF invalide ou manquant.');
                return $this->redirectToRoute('app_equipement_index');
            }

            try {
                $ancienneValeur = json_encode(['nom' => $equipement->getNom()]);
                $em->remove($equipement);
                $em->flush();
                $this->historiqueLoggerService->log('suppression', $equipement, $ancienneValeur, null, 'Équipement supprimé');
                $this->addFlash('success', 'Équipement supprimé avec succès.');

                if ($request->isXmlHttpRequest()) {
                    return new Response(null, 204); // Réponse AJAX pour suppression réussie
                }
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['error' => $e->getMessage()], 500);
                }
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

    #[Route('/{id}/new-panne', name: 'app_equipement_new_panne', methods: ['GET', 'POST'])]
    public function newPanne(Request $request, Equipement $equipement): Response
    {
        $panne = new Panne();
        $panne->setEquipement($equipement);
        $panne->setCodeInventaire($equipement->getCodeInventaire());
        $form = $this->createForm(PanneType::class, $panne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Récupérer le statut de la panne
                $panneStatut = $panne->getStatut();

                // Mettre à jour le statut de l'équipement en fonction de la panne
                if (in_array($panneStatut, ['En cours', 'En attente'])) {
                    $equipement->setStatut('en panne');
                } elseif ($panneStatut === 'Résolu') {
                    // Vérifier s'il n'y a plus de pannes actives
                    $activePannes = $equipement->getPannes()->filter(function (Panne $p) {
                        return in_array($p->getStatut(), ['En cours', 'En attente']);
                    });
                    if ($activePannes->isEmpty()) {
                        $equipement->setStatut('fonctionnel');
                    }
                }

                $this->em->persist($panne);
                $this->em->flush();

                $this->historiqueLoggerService->logAction(
                    'ajout_panne',
                    'Nouvelle panne ajoutée pour l\'équipement ' . $equipement->getNom(),
                    null,
                    $panne->getId(),
                    ['nouvelle_valeur' => json_encode(['statut' => $panne->getStatut()])]
                );

                $this->addFlash('success', 'La panne a été ajoutée avec succès. Statut de l\'équipement mis à jour.');
                return $this->redirectToRoute('app_equipement_show', ['id' => $equipement->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
            }
        }

        return $this->render('panne/new.html.twig', [
            'equipement' => $equipement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/panne/{id}/edit', name: 'app_panne_edit', methods: ['GET', 'POST'])]
    public function editPanne(Request $request, Panne $panne): Response
    {
        $equipement = $panne->getEquipement();
        $form = $this->createForm(PanneType::class, $panne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $oldStatut = $panne->getStatut(); // Pour référence (non utilisé ici)
                $newStatut = $form->get('statut')->getData();

                // Mettre à jour le statut de l'équipement
                if (in_array($newStatut, ['En cours', 'En attente'])) {
                    $equipement->setStatut('en panne');
                } elseif ($newStatut === 'Résolu') {
                    $activePannes = $equipement->getPannes()->filter(function (Panne $p) {
                        return in_array($p->getStatut(), ['En cours', 'En attente']);
                    });
                    if ($activePannes->isEmpty()) {
                        $equipement->setStatut('fonctionnel');
                    }
                }

                $this->em->flush();

                $this->historiqueLoggerService->logAction(
                    'modification_panne',
                    'Panne modifiée pour l\'équipement ' . $equipement->getNom(),
                    null,
                    $panne->getId(),
                    ['nouvelle_valeur' => json_encode(['statut' => $newStatut])]
                );

                $this->addFlash('success', 'La panne a été modifiée avec succès. Statut de l\'équipement mis à jour.');
                return $this->redirectToRoute('app_equipement_show', ['id' => $equipement->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
            }
        }

        return $this->render('panne/edit.html.twig', [
            'panne' => $panne,
            'form' => $form->createView(),
        ]);
    }

    // Route pour l'API des notifications (pour les mises à jour AJAX)
    #[Route('/api/notification-counts', name: 'app_notification_counts', methods: ['GET'])]
    public function getNotificationCounts(): JsonResponse
    {
        return new JsonResponse($this->getGlobalVariables());
    }
}