<?php

namespace App\EventSubscriber;

use App\Entity\Equipement;
use App\Entity\Historique;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class HistoriqueSubscriber implements EventSubscriber
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function getSubscribedEvents(): array
    {
        return [Events::preUpdate];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getEntity();

        // On ne gère que les modifications d'Équipements ici
        if (!$entity instanceof Equipement) {
            return;
        }

        foreach ($args->getEntityChangeSet() as $field => [$oldValue, $newValue]) {
            $historique = new Historique();
            $historique->setDateEvenement(new \DateTime());
            $historique->setTypeEvenement("Modification de l'équipement");
            $historique->setAncienneValeur((string) $oldValue);
            $historique->setNouvelleValeur((string) $newValue);
            $historique->setCommentaire("Champ modifié : $field");

            // Rattachement à l'équipement
            $historique->setEquipement($entity);

            // Récupération de l'utilisateur connecté
            $user = $this->security->getUser();
            if ($user) {
                $historique->setUtilisateurAction($user->getId());
            }

            $this->em->persist($historique);
        }
    }
}
