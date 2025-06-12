<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;

class SecurityService
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        Security $security,
        EntityManagerInterface $entityManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * Vérifie si l'utilisateur connecté peut accéder à une ressource
     */
    public function canAccess(string $resource, ?int $resourceId = null): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        switch ($resource) {
            case 'utilisateur_list':
                return $this->authorizationChecker->isGranted('ROLE_ADMIN');
                
            case 'utilisateur_create':
                return $this->authorizationChecker->isGranted('ROLE_ADMIN');
                
            case 'utilisateur_edit':
                if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
                    return true;
                }
                // Utilisateur peut modifier son propre profil
                if ($resourceId && $user->getUtilisateur()?->getId() === $resourceId) {
                    return true;
                }
                return false;
                
            case 'utilisateur_delete':
                return $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN');
                
            case 'utilisateur_view':
                if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
                    return true;
                }
                // Utilisateur peut voir son propre profil
                if ($resourceId && $user->getUtilisateur()?->getId() === $resourceId) {
                    return true;
                }
                return false;
                
            case 'equipement_manage':
                return $this->authorizationChecker->isGranted('ROLE_ADMIN') ||
                       $this->authorizationChecker->isGranted('ROLE_MODERATOR');
                       
            default:
                return false;
        }
    }

    /**
     * Retourne les rôles disponibles dans l'application
     */
    public function getAvailableRoles(): array
    {
        return [
            'ROLE_USER' => 'Utilisateur',
            'ROLE_MODERATOR' => 'Modérateur', 
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_SUPER_ADMIN' => 'Super Administrateur'
        ];
    }

    /**
     * Vérifie si un utilisateur peut être promu/rétrogradé
     */
    public function canChangeUserRole(User $targetUser, string $newRole): bool
    {
        $currentUser = $this->security->getUser();
        
        if (!$currentUser instanceof User) {
            return false;
        }

        // Seuls les admins peuvent changer les rôles
        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // Un utilisateur ne peut pas modifier son propre rôle
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        // Seuls les super admins peuvent créer d'autres super admins
        if ($newRole === 'ROLE_SUPER_ADMIN' && 
            !$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return false;
        }

        // Un admin ne peut pas rétrograder un super admin
        if (in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles()) && 
            !$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return false;
        }

        return true;
    }

    /**
     * Obtient les utilisateurs que l'utilisateur connecté peut gérer
     */
    public function getManagedUsers(): array
    {
        $currentUser = $this->security->getUser();
        
        if (!$currentUser instanceof User) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('u')
                    ->from(User::class, 'u')
                    ->where('u.id != :currentUserId')
                    ->setParameter('currentUserId', $currentUser->getId());

        // Les super admins peuvent gérer tout le monde
        if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return $queryBuilder->getQuery()->getResult();
        }

        // Les admins peuvent gérer tous sauf les super admins
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $queryBuilder->andWhere('u.roles NOT LIKE :superAdminRole')
                        ->setParameter('superAdminRole', '%ROLE_SUPER_ADMIN%');
            return $queryBuilder->getQuery()->getResult();
        }

        // Les autres ne peuvent gérer personne
        return [];
    }

    /**
     * Détermine la page d'accueil selon le rôle de l'utilisateur
     */
    public function getHomePageRoute(): string
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return 'app_admin_dashboard';
        }

        if ($this->authorizationChecker->isGranted('ROLE_MODERATOR')) {
            return 'app_moderator_dashboard';
        }

        return 'app_user_dashboard';
    }

    /**
     * Vérifie si l'utilisateur connecté peut voir les logs d'audit
     */
    public function canViewAuditLogs(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN');
    }

    /**
     * Vérifie si l'utilisateur connecté peut exporter des données
     */
    public function canExportData(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN');
    }

    /**
     * Obtient le niveau d'autorisation de l'utilisateur (pour les templates)
     */
    public function getUserAuthLevel(): string
    {
        if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return 'super_admin';
        }
        
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return 'admin';
        }
        
        if ($this->authorizationChecker->isGranted('ROLE_MODERATOR')) {
            return 'moderator';
        }
        
        return 'user';
    }
}