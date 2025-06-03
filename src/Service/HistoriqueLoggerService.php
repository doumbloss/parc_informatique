<?php

namespace App\Service;

use App\Entity\Historique;
use App\Entity\Equipement;
use App\Entity\Panne;
use App\Entity\User;
use App\Entity\Localisation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HistoriqueLoggerService
{
    private EntityManagerInterface $em;
    private ?User $currentUser;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;

        // Récupère l'utilisateur connecté sans utiliser SecurityBundle
        $token = $tokenStorage->getToken();
        $this->currentUser = $token && $token->getUser() instanceof User ? $token->getUser() : null;
    }

    public function log(string $type, ?Equipement $equipement, ?string $ancienneValeur = null, ?string $nouvelleValeur = null, ?string $commentaire = null): void
    {
        $historique = new Historique();
        $historique->setTypeEvenement($type);
        $historique->setAncienneValeur($ancienneValeur);
        $historique->setNouvelleValeur($nouvelleValeur);
        $historique->setCommentaire($commentaire);
        $historique->setEquipement($equipement);

        // Enregistre l'ID de l'utilisateur connecté ou 0 si aucun
        $historique->setUtilisateurAction($this->currentUser ? $this->currentUser->getId() : 0);

        $this->em->persist($historique);
        $this->em->flush();
    }

    public function logPanneChange(Panne $panne, string $action, array $oldData = null, ?User $user = null): void
    {
        $equipement = $panne->getEquipement();
        $historique = new Historique();

        $historique->setDate(new \DateTimeImmutable());
        $historique->setUser($user ?? $this->currentUser);
        $historique->setEquipement($equipement);
        $historique->setAction($action . ' de panne');

        $details = [
            'codeInventaire' => $panne->getCodeInventaire(),
            'description' => $panne->getDescription(),
            'statut' => $panne->getStatut(),
            'dateSignalement' => $panne->getDateSignalement()?->format('Y-m-d'),
            'dateResolution' => $panne->getDateResolution()?->format('Y-m-d'),
            'typeIntervention' => $panne->getTypeIntervention(),
            'intervenantId' => $panne->getIntervenantId(),
        ];

        if ($oldData) {
            $details = [
                'ancien' => $oldData,
                'nouveau' => $details,
            ];
        }

        $historique->setDetails(json_encode($details, JSON_UNESCAPED_UNICODE));

        $this->em->persist($historique);
        $this->em->flush();
    }

    public function logLocalisationChange(Localisation $localisation, string $actionType, ?array $oldData = null, ?User $user = null): void
    {
        $historique = new Historique();
        $historique->setTypeEvenement($actionType);
        $historique->setDateEvenement(new \DateTime());
        $historique->setUtilisateurAction(($user ?? $this->currentUser)?->getId() ?? 0);
        $historique->setCommentaire("Changement sur une localisation.");

        $data = [
            'nomBatiment' => $localisation->getNomBatiment(),
            'etage' => $localisation->getEtage(),
            'salle' => $localisation->getSalle(),
            'codeLocal' => $localisation->getCodeLocal(),
            'responsable' => $localisation->getResponsable(),
            'latitude' => $localisation->getLatitude(),
            'longitude' => $localisation->getLongitude(),
        ];

        if ($actionType === 'Modification' && $oldData) {
            $historique->setAncienneValeur(json_encode($oldData));
        }

        $historique->setNouvelleValeur(json_encode($data));

        $this->em->persist($historique);
        $this->em->flush();
    }
}
