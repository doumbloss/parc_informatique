<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\AuditLog;
use App\Entity\Equipement;
use App\Entity\Localisation;
use App\Entity\Licence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Util\ClassUtils;

class HistoriqueLoggerService
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private LoggerInterface $logger;
    private bool $isLogging = false; // Drapeau pour éviter la récursivité

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * Enregistre une action dans les logs d'audit
     */
    public function logAction(string $action, string $description, ?User $user = null, ?int $targetId = null, $additionalData = []): void
    {
        if ($this->isLogging) {
            $this->logger->warning('Recursive logging detected, skipping', ['action' => $action]);
            return;
        }

        try {
            $this->isLogging = true;
            $request = $this->requestStack->getCurrentRequest();
            $auditLog = new AuditLog();
            $auditLog->setAction($action);
            $auditLog->setDescription($description);
            $auditLog->setUser($user);
            $auditLog->setTargetId($targetId);
            $auditLog->setCreatedAt(new \DateTime());

            if ($request) {
                $auditLog->setIpAddress($request->getClientIp());
                $auditLog->setUserAgent($request->headers->get('User-Agent'));
                $auditLog->setRoute($request->attributes->get('_route'));
            }

            $sanitizedData = $this->sanitizeData($additionalData);
            if (!empty($sanitizedData)) {
                $auditLog->setAdditionalData(json_encode($sanitizedData));
            }

            $this->entityManager->persist($auditLog);
            $this->entityManager->flush();

            $this->logger->info('User action logged', [
                'user' => $user ? $user->getEmail() : 'anonymous',
                'action' => $action,
                'description' => $description,
                'target_id' => $targetId,
                'ip' => $request ? $request->getClientIp() : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to log user action', [
                'error' => $e->getMessage(),
                'action' => $action,
                'description' => $description
            ]);
        } finally {
            $this->isLogging = false; // Réinitialisation du drapeau
        }
    }

    /**
     * Méthode appelée lors de la création d'une entité (via listener Doctrine)
     */
    public function onCreate(LifecycleEventArgs $args): void
        {
            $entity = $args->getEntity();
            if ($entity instanceof AuditLog || $this->isLogging) {
                return;
            }

            $user = $this->getCurrentUser();
            $entityName = ClassUtils::getRealClass($entity);

            if ($entity instanceof Equipement || $entity instanceof Localisation || $entity instanceof Licence) {
                $this->logger->debug('Processing onCreate for entity', ['class' => $entityName, 'id' => $entity->getId() ?? 'N/A']);
                $this->logAction(
                    'CREATE_' . (new \ReflectionClass($entity))->getShortName(),
                    "Création de {$entityName} (ID: " . ($entity->getId() ?? 'N/A') . ")",
                    $user,
                    $entity->getId(),
                    $this->sanitizeEntityData($entity)
                );
            }
        }

   /**
 * Méthode appelée lors de la mise à jour d'une entité (via listener Doctrine)
 */
public function onUpdate(LifecycleEventArgs $args): void
{
    $entity = $args->getEntity();
    if ($entity instanceof AuditLog || $this->isLogging) {
        return;
    }

    $user = $this->getCurrentUser();
    $uow = $this->entityManager->getUnitOfWork();
    $entityName = ClassUtils::getRealClass($entity);

    if ($entity instanceof Equipement || $entity instanceof Localisation || $entity instanceof Licence) {
        $changeSet = $uow->getEntityChangeSet($entity);
        if (!empty($changeSet)) {
            $this->logger->debug('Processing onUpdate for entity', ['class' => $entityName, 'id' => $entity->getId() ?? 'N/A', 'changes' => $changeSet]);
            $oldData = array_map(function ($changes) {
                return $changes[0] ?? null;
            }, $changeSet);
            $this->logAction(
                'UPDATE_' . (new \ReflectionClass($entity))->getShortName(),
                "Modification de {$entityName} (ID: " . ($entity->getId() ?? 'N/A') . ")",
                $user,
                $entity->getId(),
                ['old_data' => $oldData, 'new_data' => $this->sanitizeEntityData($entity)]
            );
        }
    }
}
/**
 * Méthode appelée lors de la suppression d'une entité (via listener Doctrine)
 */
public function onDelete(LifecycleEventArgs $args): void
{
    $entity = $args->getEntity();
    if ($entity instanceof AuditLog || $this->isLogging) {
        return;
    }

    $user = $this->getCurrentUser();
    $entityName = ClassUtils::getRealClass($entity);

    if ($entity instanceof Equipement || $entity instanceof Localisation || $entity instanceof Licence) {
        $this->logger->debug('Processing onDelete for entity', ['class' => $entityName, 'id' => $entity->getId() ?? 'N/A']);
        $this->logAction(
            'DELETE_' . (new \ReflectionClass($entity))->getShortName(),
            "Suppression de {$entityName} (ID: " . ($entity->getId() ?? 'N/A') . ")",
            $user,
            $entity->getId(),
            $this->sanitizeEntityData($entity)
        );
    }
}

    /**
     * Récupère l'utilisateur courant (si disponible)
     */
    private function getCurrentUser(): ?User
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->hasPreviousSession()) {
            $tokenStorage = $request->getSession()->get('security.token.storage');
            if ($tokenStorage && method_exists($tokenStorage, 'getToken') && $tokenStorage->getToken()) {
                $user = $tokenStorage->getToken()->getUser();
                return $user instanceof User ? $user : null;
            }
        }
        return null;
    }

    /**
     * Sanitize entity data to avoid circular references or invalid types
     */
    private function sanitizeEntityData($entity): array
    {
        if (!is_object($entity)) {
            return [];
        }
        $data = get_object_vars($entity);
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                unset($data[$key]); // Évite les relations ou objets complexes
            } elseif ($value === null) {
                $data[$key] = 'N/A'; // Remplace les null par une valeur par défaut
            }
        }
        return $data;
    }

    /**
     * Sanitize any data input to ensure it can be JSON-encoded
     */
    private function sanitizeData($data): array
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeData'], $data);
        } elseif (is_object($data)) {
            return $this->sanitizeEntityData($data);
        }
        return [];
    }

    /**
     * Récupère les logs d'audit avec filtres
     */
    public function getAuditLogs(
        ?User $user = null,
        ?string $action = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null,
        int $limit = 100
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('a')
                    ->from(AuditLog::class, 'a')
                    ->orderBy('a.createdAt', 'DESC')
                    ->setMaxResults($limit);

        if ($user) {
            $queryBuilder->andWhere('a.user = :user')
                        ->setParameter('user', $user);
        }

        if ($action) {
            $queryBuilder->andWhere('a.action LIKE :action')
                        ->setParameter('action', '%' . $action . '%');
        }

        if ($fromDate) {
            $queryBuilder->andWhere('a.createdAt >= :fromDate')
                        ->setParameter('fromDate', $fromDate);
        }

        if ($toDate) {
            $queryBuilder->andWhere('a.createdAt <= :toDate')
                        ->setParameter('toDate', $toDate);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Log des tentatives de connexion
     */
    public function logLoginAttempt(string $email, bool $success, ?string $failureReason = null): void
    {
        $action = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        $description = $success 
            ? "Connexion réussie pour {$email}" 
            : "Échec de connexion pour {$email}" . ($failureReason ? " : {$failureReason}" : "");

        $this->logAction($action, $description, null, null, [
            'email' => $email,
            'success' => $success,
            'failure_reason' => $failureReason
        ]);
    }

    /**
     * Log des actions sensibles
     */
    public function logSensitiveAction(string $action, User $user, array $context = []): void
    {
        $this->logAction(
            'SENSITIVE_' . strtoupper($action),
            "Action sensible: {$action}",
            $user,
            null,
            $context
        );

        if (in_array($action, ['password_change', 'role_change', 'account_deletion'])) {
            $this->logger->warning('Sensitive action performed', [
                'user' => $user->getEmail(),
                'action' => $action,
                'context' => $context
            ]);
        }
    }

    /**
     * Nettoie les anciens logs
     */
    public function cleanOldLogs(\DateTime $beforeDate): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $query = $queryBuilder->delete(AuditLog::class, 'a')
                             ->where('a.createdAt < :beforeDate')
                             ->setParameter('beforeDate', $beforeDate)
                             ->getQuery();

        return $query->execute();
    }

    /**
     * Obtient les statistiques d'activité
     */
    public function getActivityStats(\DateTime $fromDate, \DateTime $toDate): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT action, COUNT(*) as count, COUNT(DISTINCT user_id) as unique_users
            FROM audit_log 
            WHERE created_at BETWEEN :fromDate AND :toDate
            GROUP BY action
            ORDER BY count DESC
        ';
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'fromDate' => $fromDate->format('Y-m-d H:i:s'),
            'toDate' => $toDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Détecte les activités suspectes
     */
    public function detectSuspiciousActivity(): array
    {
        $suspicious = [];
        $failedLogins = $this->getRecentFailedLogins();
        if (count($failedLogins) > 5) {
            $suspicious[] = [
                'type' => 'multiple_failed_logins',
                'count' => count($failedLogins),
                'message' => 'Nombreuses tentatives de connexion échouées'
            ];
        }

        $unusualIPs = $this->getUnusualIPAddresses();
        if (!empty($unusualIPs)) {
            $suspicious[] = [
                'type' => 'unusual_ip',
                'ips' => $unusualIPs,
                'message' => 'Connexions depuis des adresses IP inhabituelles'
            ];
        }

        return $suspicious;
    }

    private function getRecentFailedLogins(): array
    {
        $since = new \DateTime('-1 hour');
        return $this->getAuditLogs(null, 'LOGIN_FAILED', $since);
    }

    private function getUnusualIPAddresses(): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT DISTINCT a1.ip_address
            FROM audit_log a1
            WHERE a1.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            AND a1.ip_address NOT IN (
                SELECT DISTINCT a2.ip_address 
                FROM audit_log a2 
                WHERE a2.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND DATE_SUB(NOW(), INTERVAL 1 DAY)
            )
            AND a1.ip_address IS NOT NULL
        ';
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return array_column($result->fetchAllAssociative(), 'ip_address');
    }
}