<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_audit_action', columns: ['action'])]
#[ORM\Index(name: 'idx_audit_created', columns: ['created_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $targetId = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $additionalData = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(?int $targetId): self
    {
        $this->targetId = $targetId;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getAdditionalData(): ?string
    {
        return $this->additionalData;
    }

    public function setAdditionalData(?string $additionalData): self
    {
        $this->additionalData = $additionalData;
        return $this;
    }

    public function getAdditionalDataArray(): array
    {
        return $this->additionalData ? json_decode($this->additionalData, true) : [];
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Retourne une description formatée de l'action pour l'affichage
     */
    public function getFormattedDescription(): string
    {
        $userName = $this->user ? $this->user->getFullName() : 'Système';
        $date = $this->createdAt ? $this->createdAt->format('d/m/Y H:i:s') : '';
        
        return sprintf(
            '[%s] %s - %s',
            $date,
            $userName,
            $this->description
        );
    }

    /**
     * Retourne la classe CSS pour le type d'action (pour l'affichage)
     */
    public function getActionClass(): string
    {
        return match ($this->action) {
            'LOGIN_SUCCESS' => 'success',
            'LOGIN_FAILED' => 'danger',
            'CREATION' => 'info',
            'MODIFICATION' => 'warning',
            'SUPPRESSION' => 'danger',
            'CONSULTATION' => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Retourne l'icône appropriée pour le type d'action
     */
    public function getActionIcon(): string
    {
        return match ($this->action) {
            'LOGIN_SUCCESS' => 'fas fa-sign-in-alt',
            'LOGIN_FAILED' => 'fas fa-times-circle',
            'CREATION' => 'fas fa-plus',
            'MODIFICATION' => 'fas fa-edit',
            'SUPPRESSION' => 'fas fa-trash',
            'CONSULTATION' => 'fas fa-eye',
            default => 'fas fa-info-circle'
        };
    }
}