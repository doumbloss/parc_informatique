<?php

namespace App\Twig;

use App\Service\NotificationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_notification_count', [$this, 'getNotificationCount']),
        ];
    }

    public function getNotificationCount(): int
    {
        return $this->notificationService->getNotificationCount();
    }
}