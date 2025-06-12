<?php

namespace App\EventListener;

use App\Service\HistoriqueLoggerService;
use Doctrine\ORM\Event\LifecycleEventArgs;

class AuditLogListener
{
    private HistoriqueLoggerService $loggerService;

    public function __construct(HistoriqueLoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if (!($entity instanceof \App\Entity\AuditLog)) {
            $this->loggerService->onCreate($args);
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if (!($entity instanceof \App\Entity\AuditLog)) {
            $this->loggerService->onUpdate($args);
        }
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if (!($entity instanceof \App\Entity\AuditLog)) {
            $this->loggerService->onDelete($args);
        }
    }
}