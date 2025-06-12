<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class RoleManagerService
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private HistoriqueLoggerService $historiqueLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        HistoriqueLoggerService $historiqueLogger
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->historiqueLogger = $historiqueLogger;
    }

    /**
     * Assigne un rôle à un utilisateur
     */
    public function assignRole(User $user, string $role): bool
    {
        $currentUser = $this->security->getUser();
        
        if (!$this->canAssignRole($currentUser, $role)) {
            return false;
        }

        $oldRoles = $user->getRoles();
        $newRoles = array_unique(array_merge($oldRoles, [$role]));
        
        $user->setRoles($newRoles);
        
        // Synchroniser avec l'entité Utilisateur si elle existe
        if ($user->getUtilisateur()) {
            $this->syncUtilisateurRole($user->getUtilisateur(), $role);
        }

        $this->entityManager->flush();

        // Logger le changement
        $this->historiqueLogger->logSensitiveAction('role_assignment', $currentUser, [
            'target_user' => $user->getEmail(),
            'assigned_role' => $role,
            'previous_roles' => $oldRoles,
            'new_roles' => $newRoles
        ]);

        return true;
    }

    /**
     * Retire un rôle d'un utilisateur
     */
    public function removeRole(User $user, string $role): bool
    {
        $currentUser = $this->security->getUser();
        
        if (!$this->canRemoveRole($currentUser, $user, $role)) {
            return false;
        }

        $oldRoles = $user->getRoles();
        $newRoles = array_diff($oldRoles, [$role]);
        
        // S'assurer qu'il reste au moins ROLE_USER
        if (empty($newRoles) || !in_array('ROLE_USER', $newRoles)) {
            $newRoles[] = 'ROLE_USER';
        }
        
        $user->setRoles(array_values($newRoles));
        $this->entityManager->flush();

        // Logger le changement
        $this->historiqueLogger->logSensitiveAction('role_removal', $currentUser, [
            'target_user' => $user->getEmail(),
            'removed_role' => $role,
            'previous_roles' => $oldRoles,
            'new_roles' => $newRoles
        ]);

        return true;
    }

    /**
     * Change complètement les rôles d'un utilisateur
     */
    public function setRoles(User $user, array $roles): bool
    {
        $currentUser = $this->security->getUser();
        
        foreach ($roles as $role) {
            if (!$this->canAssignRole($currentUser, $role)) {
                return false;
            }
        }

        $oldRoles = $user->getRoles();
        
        // S'assurer qu'il y a au moins ROLE_USER
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        
        $user->setRoles(array_unique($roles));
        
        // Synchroniser avec l'entité Utilisateur
        if ($user->getUtilisateur()) {
            $mainRole = $this->getMainRole($roles);
            $this->syncUtilisateurRole($user->getUtilisateur(), $mainRole);
        }

        $this->entityManager->flush();

        // Logger le changement
        $this->historiqueLogger->logSensitiveAction('roles_update', $currentUser, [
            'target_user' => $user->getEmail(),
            'previous_roles' => $oldRoles,
            'new_roles' => $roles
        ]);

        return true;
    }

    /**
     * Vérifie si l'utilisateur courant peut assigner un rôle
     */
    public function canAssignRole(?User $currentUser, string $role): bool
    {
        if (!$currentUser) {
            return false;
        }

        $currentRoles = $currentUser->getRoles();

        // Seuls les super admins peuvent créer d'autres super admins
        if ($role === 'ROLE_SUPER_ADMIN' && !in_array('ROLE_SUPER_ADMIN', $currentRoles)) {
            return false;
        }

        // Les admins peuvent assigner tous les rôles sauf SUPER_ADMIN
        if (in_array('ROLE_ADMIN', $currentRoles) && $role !== 'ROLE_SUPER_ADMIN') {
            return true;
        }

        // Les super admins peuvent tout faire
        if (in_array('ROLE_SUPER_ADMIN', $currentRoles)) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur courant peut retirer un rôle
     */
    public function canRemoveRole(?User $currentUser, User $targetUser, string $role): bool
    {
        if (!$currentUser) {
            return false;
        }

        // On ne peut pas modifier ses propres rôles
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        $currentRoles = $currentUser->getRoles();
        $targetRoles = $targetUser->getRoles();

        // Un admin ne peut pas retirer des rôles à un super admin
        if (in_array('ROLE_SUPER_ADMIN', $targetRoles) && 
            !in_array('ROLE_SUPER_ADMIN', $currentRoles)) {
            return false;
        }

        // Les super admins peuvent tout faire
        if (in_array('ROLE_SUPER_ADMIN', $currentRoles)) {
            return true;
        }

        // Les admins peuvent retirer tous les rôles sauf SUPER_ADMIN
        if (in_array('ROLE_ADMIN', $currentRoles) && $role !== 'ROLE_SUPER_ADMIN') {
            return true;
        }

        return false;
    }

    /**
     * Obtient la liste des rôles assignables par l'utilisateur courant
     */
    public function getAssignableRoles(?User $currentUser): array
    {
        if (!$currentUser) {
            return [];
        }

        $allRoles = [
            'ROLE_USER' => 'Utilisateur',
            'ROLE_MODERATOR' => 'Modérateur',
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_SUPER_ADMIN' => 'Super Administrateur'
        ];

        $currentRoles = $currentUser->getRoles();

        // Super admin peut tout assigner
        if (in_array('ROLE_SUPER_ADMIN', $currentRoles)) {
            return $allRoles;
        }

        // Admin peut assigner tout sauf SUPER_ADMIN
        if (in_array('ROLE_ADMIN', $currentRoles)) {
            unset($allRoles['ROLE_SUPER_ADMIN']);
            return $allRoles;
        }

        return [];
    }

    /**
     * Synchronise le rôle avec l'entité Utilisateur
     */
    private function syncUtilisateurRole(Utilisateur $utilisateur, string $role): void
    {
        $roleMapping = [
            'ROLE_USER' => 'USER',
            'ROLE_MODERATOR' => 'MODERATOR',
            'ROLE_ADMIN' => 'ADMIN',
            'ROLE_SUPER_ADMIN' => 'SUPER_ADMIN'
        ];

        $utilisateurRole = $roleMapping[$role] ?? 'USER';
        $utilisateur->setRole($utilisateurRole);
    }

    /**
     * Détermine le rôle principal parmi une liste de rôles
     */
    private function getMainRole(array $roles): string
    {
        $hierarchy = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_USER'];
        
        foreach ($hierarchy as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }
        
        return 'ROLE_USER';
    }

    /**
     * Obtient les utilisateurs par rôle
     */
    public function getUsersByRole(string $role): array
    {
        $repository = $this->entityManager->getRepository(User::class);
        
        return $repository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs par rôle
     */
    public function countUsersByRole(): array
    {
        $repository = $this->entityManager->getRepository(User::class);
        $allUsers = $repository->findAll();
        
        $roleCounts = [
            'ROLE_USER' => 0,
            'ROLE_MODERATOR' => 0,
            'ROLE_ADMIN' => 0,
            'ROLE_SUPER_ADMIN' => 0
        ];
        
        foreach ($allUsers as $user) {
            $userRoles = $user->getRoles();
            
            // Compter chaque rôle spécifique (pas seulement le principal)
            foreach ($roleCounts as $role => $count) {
                if (in_array($role, $userRoles)) {
                    $roleCounts[$role]++;
                }
            }
        }
        
        return $roleCounts;
    }

    /**
     * Vérifie si un utilisateur a un rôle spécifique
     */
    public function hasRole(User $user, string $role): bool
    {
        return in_array($role, $user->getRoles());
    }

    /**
     * Obtient le rôle le plus élevé d'un utilisateur
     */
    public function getHighestRole(User $user): string
    {
        return $this->getMainRole($user->getRoles());
    }

    /**
     * Vérifie si un utilisateur peut gérer un autre utilisateur
     */
    public function canManageUser(?User $currentUser, User $targetUser): bool
    {
        if (!$currentUser || $currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        $currentHighestRole = $this->getHighestRole($currentUser);
        $targetHighestRole = $this->getHighestRole($targetUser);

        $hierarchy = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_USER'];
        $currentLevel = array_search($currentHighestRole, $hierarchy);
        $targetLevel = array_search($targetHighestRole, $hierarchy);

        // Plus le niveau est bas dans le tableau, plus le rôle est élevé
        return $currentLevel < $targetLevel;
    }

    /**
     * Obtient les statistiques des rôles
     */
    public function getRoleStatistics(): array
    {
        $counts = $this->countUsersByRole();
        $total = array_sum($counts);
        
        $statistics = [];
        foreach ($counts as $role => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $statistics[$role] = [
                'count' => $count,
                'percentage' => $percentage,
                'label' => $this->getRoleLabel($role)
            ];
        }
        
        return $statistics;
    }

    /**
     * Obtient le libellé d'un rôle
     */
    private function getRoleLabel(string $role): string
    {
        $labels = [
            'ROLE_USER' => 'Utilisateur',
            'ROLE_MODERATOR' => 'Modérateur',
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_SUPER_ADMIN' => 'Super Administrateur'
        ];
        
        return $labels[$role] ?? $role;
    }

    /**
     * Obtient tous les utilisateurs avec leurs rôles principaux
     */
    public function getAllUsersWithMainRole(): array
    {
        $repository = $this->entityManager->getRepository(User::class);
        $users = $repository->findAll();
        
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'user' => $user,
                'mainRole' => $this->getHighestRole($user),
                'allRoles' => $user->getRoles()
            ];
        }
        
        return $result;
    }

    /**
     * Valide un rôle
     */
    public function isValidRole(string $role): bool
    {
        $validRoles = ['ROLE_USER', 'ROLE_MODERATOR', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
        return in_array($role, $validRoles);
    }
}