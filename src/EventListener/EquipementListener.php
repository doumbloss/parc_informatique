<?php

namespace App\EventListener;

use App\Entity\Equipement;
use Doctrine\ORM\Event\PrePersistEventArgs;

class EquipementListener
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $equipement = $args->getObject();

        // Vérifier que l'entité est bien un Equipement
        if ($equipement instanceof Equipement) {
            if (!$equipement->getCodeInventaire()) {
                $entityManager = $args->getEntityManager();
                $equipement->generateUniqueCodeInventaire($entityManager);
            }
        }
    }
}