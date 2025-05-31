<?php

namespace App\Service;

use App\Entity\Equipement;
use App\Entity\Panne;
use Doctrine\ORM\EntityManagerInterface;

class AlertService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getEquipementAlerts(): int
    {
        try {
            return $this->em->getRepository(Equipement::class)
                ->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.etat IN (:etats)')
                ->setParameter('etats', ['en_panne', 'maintenance_requise', 'obsolete'])
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getPanneAlerts(): int
    {
        try {
            // Vérifiez si l'entité Panne existe
            if (!class_exists('App\Entity\Panne')) {
                return 0;
            }

            return $this->em->getRepository(Panne::class)
                ->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.statut != :statut OR p.statut IS NULL')
                ->setParameter('statut', 'resolu')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getAllAlerts(): array
    {
        return [
            'equipement_alerts' => $this->getEquipementAlerts(),
            'panne_alerts' => $this->getPanneAlerts(),
        ];
    }
}