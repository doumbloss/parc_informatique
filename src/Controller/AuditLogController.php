<?php

namespace App\Controller;

use App\Service\HistoriqueLoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/audit')]
class AuditLogController extends AbstractController
{
    private HistoriqueLoggerService $loggerService;

    public function __construct(HistoriqueLoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    #[Route('/logs', name: 'app_audit_logs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser(); // Utilisateur connecté, si applicable
        $action = $request->query->get('action');
        $fromDate = $request->query->get('from') ? new \DateTime($request->query->get('from')) : null;
        $toDate = $request->query->get('to') ? new \DateTime($request->query->get('to')) : null;

        $logs = $this->loggerService->getAuditLogs(
            $user,
            $action,
            $fromDate,
            $toDate
        );

        return $this->render('audit/log_index.html.twig', [
            'logs' => $logs,
            'filters' => [
                'action' => $action,
                'from' => $fromDate ? $fromDate->format('Y-m-d') : '',
                'to' => $toDate ? $toDate->format('Y-m-d') : '',
            ],
        ]);
    }
}