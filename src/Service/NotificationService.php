<?php

namespace App\Service;

use App\Repository\EquipementRepository;
use App\Repository\LicenceRepository;

class NotificationService
{
    private EquipementRepository $equipementRepository;
    private LicenceRepository $licenceRepository;

    public function __construct(EquipementRepository $equipementRepository, LicenceRepository $licenceRepository)
    {
        $this->equipementRepository = $equipementRepository;
        $this->licenceRepository = $licenceRepository;
    }

    public function getNotificationCount(): int
    {
        // Exemple : compter les équipements en panne ou licences expirant bientôt
        $equipementsEnPanne = $this->equipementRepository->findBy(['statut' => 'en_panne']);
        $licencesProchesExpiration = $this->licenceRepository->findBy([
            'dateExpiration' => new \DateTime('now + 30 days'),
        ]);

        return count($equipementsEnPanne) + count($licencesProchesExpiration);
    }
}