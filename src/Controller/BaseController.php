<?php

namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BaseController extends AbstractController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    #[Route('/base', name: 'app_base')]
    public function index(): Response
    {
        $notificationCount = $this->notificationService->getNotificationCount();
        return $this->render('base.html.twig', [
            'notificationCount' => $notificationCount,
        ]);
    }
}