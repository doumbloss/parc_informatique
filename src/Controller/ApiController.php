<?php
// src/Controller/ApiController.php
namespace App\Controller;

use App\Repository\ActivityLogRepository; // À créer si nécessaire
use App\Repository\NotificationRepository; // À créer si nécessaire
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/activity', name: 'api_activity', methods: ['GET'])]
    public function getActivity(ActivityLogRepository $activityLogRepository): JsonResponse
    {
        $activities = $activityLogRepository->findLatest(4); // Ajuster selon votre logique
        $data = array_map(function ($activity) {
            return [
                'icon' => 'plus', // À personnaliser
                'title' => $activity->getTitle(),
                'desc' => $activity->getDescription() . ' - ' . $activity->getCreatedAt()->format('G\hi'),
            ];
        }, $activities);

        return new JsonResponse(['activities' => $data]);
    }

    #[Route('/api/notifications', name: 'api_notifications', methods: ['GET'])]
    public function getNotifications(NotificationRepository $notificationRepository): JsonResponse
    {
        $notifications = $notificationRepository->findAll();
        $data = array_map(function ($notification) {
            return [
                'icon' => 'bell', // À personnaliser
                'title' => $notification->getTitle(),
                'desc' => $notification->getDescription(),
            ];
        }, $notifications);

        return new JsonResponse(['notifications' => $data]);
    }
}