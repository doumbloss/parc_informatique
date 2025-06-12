<?php

namespace App\EventSubscriber;

use App\Entity\Equipement;
use App\Entity\Panne;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class PanneSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Panne) {
                $this->updateEquipementStatus($entity, $em, $uow);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Panne) {
                $this->updateEquipementStatus($entity, $em, $uow);
            }
        }
    }

    private function updateEquipementStatus(Panne $panne, $em, $uow): void
    {
        $equipement = $panne->getEquipement();
        $panneStatut = $panne->getStatut();

        if (in_array($panneStatut, ['En cours', 'En attente'])) {
            $equipement->setStatut('en panne');
        } elseif ($panneStatut === 'Résolu') {
            $activePannes = $equipement->getPannes()->filter(function (Panne $p) {
                return in_array($p->getStatut(), ['En cours', 'En attente']);
            });
            if ($activePannes->isEmpty()) {
                $equipement->setStatut('fonctionnel');
            }
        }

        $em->persist($equipement);
        $uow->computeChangeSet($em->getClassMetadata(Equipement::class), $equipement);
    }
}