<?php
// src/EventListener/UserListener.php
namespace App\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use App\Entity\User;

class UserListener implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof User) {
            $this->syncUtilisateur($entity);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof User) {
            $this->syncUtilisateur($entity);
        }
    }

    private function syncUtilisateur(User $user): void
    {
        $utilisateur = $user->getUtilisateur();
        if ($utilisateur) {
            $utilisateur->setNom($user->getFullName());
            $utilisateur->setEmail($user->getEmail());
        }
    }
}