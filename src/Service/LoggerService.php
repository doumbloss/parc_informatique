<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ActivityLog;
use Symfony\Component\Serializer\SerializerInterface;

class LoggerService
{
    private LoggerInterface $logger;
    private Security $security;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    
    public function __construct(
        LoggerInterface $logger,
        Security $security,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function logActivity(
        string $action,
        string $entityType = null,
        int $entityId = null,
        array $context = []
    ): void {
        try {
            $user = $this->security->getUser();
            $request = $this->requestStack->getCurrentRequest();

            $activityLog = new ActivityLog();
            $activityLog->setAction($action);
            $activityLog->setEntityType($entityType);
            $activityLog->setEntityId($entityId);
            $activityLog->setUserId($user?->getId());
            $activityLog->setUserEmail($user?->getEmail() ?? 'Anonyme');
            $activityLog->setIpAddress($request?->getClientIp());
            $activityLog->setUserAgent($request?->headers->get('User-Agent'));
            $activityLog->setContext($context);
            $activityLog->setCreatedAt(new \DateTime());

            $this->entityManager->persist($activityLog);
            $this->entityManager->flush();

        } catch (\Throwable $e) {
            $this->logger->error("Erreur lors de l'enregistrement de l'activité", [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function logCreation(object $entity, array $additionalContext = []): void
    {
        $entityName = $this->getEntityName($entity);
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;

        $context = array_merge([
            'entity_data' => $this->serializeEntity($entity),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ], $additionalContext);

        $this->logActivity("Création de {$entityName}", $entityName, $entityId, $context);

        $this->logger->info("Entité créée", [
            'entity' => $entityName,
            'id' => $entityId,
            'user' => $this->getCurrentUserIdentifier()
        ]);
    }

    public function logUpdate(object $entity, array $changes = [], array $additionalContext = []): void
    {
        $entityName = $this->getEntityName($entity);
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;

        $context = array_merge([
            'changes' => $changes,
            'entity_data' => $this->serializeEntity($entity),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ], $additionalContext);

        $this->logActivity("Modification de {$entityName}", $entityName, $entityId, $context);

        $this->logger->info("Entité modifiée", [
            'entity' => $entityName,
            'id' => $entityId,
            'changes' => $changes,
            'user' => $this->getCurrentUserIdentifier()
        ]);
    }

    public function logDeletion(object $entity, array $additionalContext = []): void
    {
        $entityName = $this->getEntityName($entity);
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;

        $context = array_merge([
            'deleted_entity_data' => $this->serializeEntity($entity),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ], $additionalContext);

        $this->logActivity("Suppression de {$entityName}", $entityName, $entityId, $context);

        $this->logger->warning("Entité supprimée", [
            'entity' => $entityName,
            'id' => $entityId,
            'user' => $this->getCurrentUserIdentifier()
        ]);
    }

    public function logLogin(string $email, bool $success = true): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $context = [
            'ip_address' => $request?->getClientIp(),
            'user_agent' => $request?->headers->get('User-Agent'),
            'success' => $success,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        $action = $success ? 'Connexion réussie' : 'Tentative de connexion échouée';

        $this->logActivity($action, 'User', null, $context);

        $this->logger->{$success ? 'info' : 'warning'}("Connexion utilisateur", [
            'email' => $email,
            'ip' => $context['ip_address']
        ]);
    }

    public function logLogout(): void
    {
        $user = $this->security->getUser();

        if ($user) {
            $this->logActivity('Déconnexion', 'User', $user->getId());
            $this->logger->info("Déconnexion utilisateur", [
                'user' => $this->getCurrentUserIdentifier()
            ]);
        }
    }

    public function logBusinessError(string $message, array $context = []): void
    {
        $fullContext = array_merge([
            'user' => $this->getCurrentUserIdentifier(),
            'ip' => $this->requestStack->getCurrentRequest()?->getClientIp(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ], $context);

        $this->logActivity("Erreur métier: {$message}", null, null, $fullContext);

        $this->logger->error("Erreur métier", [
            'message' => $message,
            'context' => $context,
            'user' => $this->getCurrentUserIdentifier()
        ]);
    }

    public function logSecurityEvent(string $event, string $severity = 'warning', array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $securityContext = array_merge([
            'ip_address' => $request?->getClientIp(),
            'user_agent' => $request?->headers->get('User-Agent'),
            'referer' => $request?->headers->get('referer'),
            'user' => $this->getCurrentUserIdentifier(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ], $context);

        $this->logActivity("Événement de sécurité: {$event}", 'Security', null, $securityContext);

        $logMethod = method_exists($this->logger, $severity) ? $severity : 'warning';
        $this->logger->$logMethod("Événement de sécurité", [
            'event' => $event,
            'context' => $securityContext
        ]);
    }

    public function logPanneAction(string $action, int $panneId, array $context = []): void
    {
        $this->logActivity(
            "Panne - {$action}",
            'Panne',
            $panneId,
            array_merge($context, [
                'module' => 'gestion_pannes',
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ])
        );
    }

    public function getActivityLogs(
        int $limit = 100,
        string $action = null,
        string $entityType = null,
        int $userId = null,
        \DateTime $startDate = null,
        \DateTime $endDate = null
    ): array {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('al')
           ->from(ActivityLog::class, 'al')
           ->orderBy('al.createdAt', 'DESC')
           ->setMaxResults($limit);

        if ($action) {
            $qb->andWhere('al.action LIKE :action')
               ->setParameter('action', '%' . $action . '%');
        }

        if ($entityType) {
            $qb->andWhere('al.entityType = :entityType')
               ->setParameter('entityType', $entityType);
        }

        if ($userId) {
            $qb->andWhere('al.userId = :userId')
               ->setParameter('userId', $userId);
        }

        if ($startDate) {
            $qb->andWhere('al.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('al.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = new \DateTime("-{$daysToKeep} days");

        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->delete(ActivityLog::class, 'al')
                    ->where('al.createdAt < :cutoffDate')
                    ->setParameter('cutoffDate', $cutoffDate)
                    ->getQuery();

        return $query->execute();
    }

    private function getEntityName(object $entity): string
    {
        return (new \ReflectionClass($entity))->getShortName();
    }

    private function serializeEntity(object $entity): string
    {
        return $this->serializer->serialize($entity, 'json');
    }

    private function getCurrentUserIdentifier(): string
    {
        $user = $this->security->getUser();
        return $user ? ($user->getUserIdentifier() ?? $user->getEmail() ?? 'Inconnu') : 'Anonyme';
    }
}
