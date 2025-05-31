<?php

namespace App\Entity;

use App\Repository\HistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DoctrineExtensions\Query\Mysql;
use App\Entity\User;

 


#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
#[ORM\Table(name: 'historique')]
#[ORM\Index(name: 'idx_date_evenement', columns: ['date_evenement'])]
#[ORM\Index(name: 'idx_type_evenement', columns: ['type_evenement'])]
class Historique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateEvenement = null;

    #[ORM\Column(length: 100)]
    private ?string $typeEvenement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ancienneValeur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nouvelleValeur = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $utilisateurAction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(targetEntity: Equipement::class, inversedBy: 'historiques')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Equipement $equipement = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $utilisateur = null;

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }


    public function __construct()
    {
        $this->dateEvenement = new \DateTime();
    }

    // Getters & Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEvenement(): ?\DateTimeInterface
    {
        return $this->dateEvenement;
    }

    public function setDateEvenement(\DateTimeInterface $dateEvenement): static
    {
        $this->dateEvenement = $dateEvenement;
        return $this;
    }

    public function getTypeEvenement(): ?string
    {
        return $this->typeEvenement;
    }

    public function setTypeEvenement(string $typeEvenement): static
    {
        $this->typeEvenement = $typeEvenement;
        return $this;
    }

    public function getAncienneValeur(): ?string
    {
        return $this->ancienneValeur;
    }

    public function setAncienneValeur(?string $ancienneValeur): static
    {
        $this->ancienneValeur = $ancienneValeur;
        return $this;
    }

    public function getNouvelleValeur(): ?string
    {
        return $this->nouvelleValeur;
    }

    public function setNouvelleValeur(?string $nouvelleValeur): static
    {
        $this->nouvelleValeur = $nouvelleValeur;
        return $this;
    }

    public function getUtilisateurAction(): ?int
    {
        return $this->utilisateurAction;
    }

    public function setUtilisateurAction(int $utilisateurAction): static
    {
        $this->utilisateurAction = $utilisateurAction;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getEquipement(): ?Equipement
    {
        return $this->equipement;
    }

    public function setEquipement(?Equipement $equipement): static
    {
        $this->equipement = $equipement;
        return $this;
    }

    // Méthodes utilitaires
    public function getDateEvenementFormatted(): string
    {
        return $this->dateEvenement ? $this->dateEvenement->format('d/m/Y H:i:s') : '';
    }

    public function isRecent(): bool
    {
        if (!$this->dateEvenement) {
            return false;
        }
        
        $now = new \DateTime();
        $interval = $now->diff($this->dateEvenement);
        
        return $interval->days < 7; // Considéré comme récent si moins de 7 jours
    }
}