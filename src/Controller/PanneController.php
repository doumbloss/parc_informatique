<?php

namespace App\Controller;

use App\Entity\Panne;
use App\Form\PanneType;
use App\Repository\PanneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\LoggerService;

#[Route('/panne')]
class PanneController extends AbstractController
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    #[Route('/', name: 'app_panne_index', methods: ['GET'])]
    public function index(PanneRepository $panneRepository): Response
    {
        $this->loggerService->logActivity(
            'Consultation de la liste des pannes',
            'Panne',
            null,
            ['module' => 'gestion_pannes', 'action_type' => 'consultation']
        );

        return $this->render('panne/index.html.twig', [
            'pannes' => $panneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_panne_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $panne = new Panne();
        $form = $this->createForm(PanneType::class, $panne);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $entityManager->persist($panne);
                    $entityManager->flush();

                    $this->loggerService->logCreation($panne, [
                        'module' => 'gestion_pannes',
                        'form_data' => $request->request->all()
                    ]);

                    $this->loggerService->logPanneAction(
                        'Création d\'une nouvelle panne',
                        $panne->getId(),
                        [
                            'description' => $panne->getDescription() ?? 'Non renseignée',
                            'status' => $panne->getStatut() ?? 'En cours',
                            'priority' => $panne->getTypeIntervention() ?? 'Normal'
                        ]
                    );

                    $this->addFlash('success', 'La panne a été créée avec succès.');

                    return $this->redirectToRoute('app_panne_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    $this->loggerService->logBusinessError(
                        'Erreur lors de la création d\'une panne',
                        [
                            'error_message' => $errorMessage,
                            'panne_data' => $request->request->all(),
                            'stack_trace' => $e->getTraceAsString()
                        ]
                    );
                    $this->addFlash('error', 'Une erreur est survenue lors de la création de la panne : ' . $errorMessage);
                }
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez vérifier les champs.');
            }
        }

        return $this->renderForm('panne/new.html.twig', [
            'panne' => $panne,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_panne_show', methods: ['GET'])]
    public function show(Panne $panne): Response
    {
        $this->loggerService->logActivity(
            'Consultation d\'une panne',
            'Panne',
            $panne->getId(),
            [
                'module' => 'gestion_pannes',
                'action_type' => 'consultation',
                'panne_title' => $panne->getCodeInventaire() ?? 'Sans titre' // Utilise codeInventaire
            ]
        );

        return $this->render('panne/show.html.twig', [
            'panne' => $panne,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_panne_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Panne $panne, EntityManagerInterface $entityManager): Response
    {
        $originalData = [
            'codeInventaire' => $panne->getCodeInventaire(), // Remplace title
            'description' => $panne->getDescription(),
            'statut' => $panne->getStatut(), // Remplace status
            'typeIntervention' => $panne->getTypeIntervention(), // Remplace priority
        ];

        $form = $this->createForm(PanneType::class, $panne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();

                $changes = [];
                $newData = [
                    'codeInventaire' => $panne->getCodeInventaire(),
                    'description' => $panne->getDescription(),
                    'statut' => $panne->getStatut(),
                    'typeIntervention' => $panne->getTypeIntervention(),
                ];

                foreach ($newData as $field => $newValue) {
                    if ($originalData[$field] !== $newValue) {
                        $changes[$field] = [
                            'old' => $originalData[$field],
                            'new' => $newValue
                        ];
                    }
                }

                $this->loggerService->logUpdate($panne, $changes, [
                    'module' => 'gestion_pannes',
                    'form_data' => $request->request->all()
                ]);

                $this->loggerService->logPanneAction(
                    'Modification d\'une panne',
                    $panne->getId(),
                    [
                        'changes' => $changes,
                        'fields_modified' => array_keys($changes)
                    ]
                );

                $this->addFlash('success', 'La panne a été modifiée avec succès.');

                return $this->redirectToRoute('app_panne_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->loggerService->logBusinessError(
                    'Erreur lors de la modification d\'une panne',
                    [
                        'panne_id' => $panne->getId(),
                        'error_message' => $e->getMessage(),
                        'changes_attempted' => $changes ?? []
                    ]
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de la panne.');
            }
        }

        return $this->renderForm('panne/edit.html.twig', [
            'panne' => $panne,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_panne_delete', methods: ['POST'])]
    public function delete(Request $request, Panne $panne, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $panne->getId(), $request->request->get('_token'))) {
            try {
                $panneData = [
                    'id' => $panne->getId(),
                    'codeInventaire' => $panne->getCodeInventaire(), // Remplace title
                    'description' => $panne->getDescription(),
                    'statut' => $panne->getStatut(), // Remplace status
                    'typeIntervention' => $panne->getTypeIntervention(), // Remplace priority
                ];

                $this->loggerService->logDeletion($panne, [
                    'module' => 'gestion_pannes',
                    'reason' => $request->request->get('delete_reason', 'Non spécifiée')
                ]);

                $this->loggerService->logPanneAction(
                    'Suppression d\'une panne',
                    $panne->getId(),
                    [
                        'deleted_data' => $panneData,
                        'confirmation_token' => 'validated'
                    ]
                );

                $entityManager->remove($panne);
                $entityManager->flush();

                $this->addFlash('success', 'La panne a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->loggerService->logBusinessError(
                    'Erreur lors de la suppression d\'une panne',
                    [
                        'panne_id' => $panne->getId(),
                        'error_message' => $e->getMessage()
                    ]
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la panne.');
            }
        } else {
            $this->loggerService->logSecurityEvent(
                'Tentative de suppression avec token CSRF invalide',
                'warning',
                [
                    'panne_id' => $panne->getId(),
                    'provided_token' => $request->request->get('_token'),
                    'expected_action' => 'delete' . $panne->getId()
                ]
            );
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('app_panne_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/logs', name: 'app_panne_logs', methods: ['GET'])]
    public function logs(Panne $panne): Response
    {
        $logs = $this->loggerService->getActivityLogs(
            limit: 50,
            entityType: 'Panne',
            userId: null,
            startDate: null,
            endDate: null
        );

        $panneSpecificLogs = array_filter($logs, function($log) use ($panne) {
            return $log->getEntityId() === $panne->getId();
        });

        $this->loggerService->logActivity(
            'Consultation de l\'historique d\'une panne',
            'Panne',
            $panne->getId(),
            ['module' => 'gestion_pannes', 'action_type' => 'consultation_logs']
        );

        return $this->render('panne/logs.html.twig', [
            'panne' => $panne,
            'logs' => $panneSpecificLogs,
        ]);
    }

    #[Route('/{id}/resolve', name: 'app_panne_resolve', methods: ['POST'])]
    public function resolve(Request $request, Panne $panne, EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $request->request->get('token');
        if (!$this->isCsrfTokenValid('resolve' . $panne->getId(), $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token de sécurité invalide.'], 400);
        }

        try {
            $resolutionNote = $request->request->get('resolution_note');
            $panne->setStatut('Résolu');
            $panne->setDateResolution(new \DateTime());
            $panne->setTypeIntervention($request->request->get('typeIntervention', $panne->getTypeIntervention()));
            $panne->setIntervenantId($request->request->get('intervenantId', $panne->getIntervenantId()));

            $entityManager->flush();

            // Log de l'action (si vous utilisez un service logger)
            if (isset($this->loggerService)) {
                $this->loggerService->logPanneAction(
                    'Résolution d\'une panne',
                    $panne->getId(),
                    [
                        'resolution_note' => $resolutionNote,
                        'status' => $panne->getStatut(),
                        'date_resolution' => $panne->getDateResolution()->format('Y-m-d H:i:s')
                    ]
                );
            }

            return new JsonResponse(['success' => true, 'message' => 'La panne a été résolue avec succès.']);
        } catch (\Exception $e) {
            // Log de l'erreur (si vous utilisez un service logger)
            if (isset($this->loggerService)) {
                $this->loggerService->logBusinessError(
                    'Erreur lors de la résolution d\'une panne',
                    [
                        'panne_id' => $panne->getId(),
                        'error_message' => $e->getMessage(),
                        'resolution_note' => $request->request->get('resolution_note')
                    ]
                );
            }
            return new JsonResponse(['success' => false, 'message' => 'Une erreur est survenue : ' . $e->getMessage()], 500);
        }
    }
}