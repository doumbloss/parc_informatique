<?php
 
namespace App\Entity;

use App\Repository\PanneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PanneRepository::class)]
class Panne
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le code d'inventaire est obligatoire.")]
    private ?string $codeInventaire = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "La date de signalement est obligatoire.")]
    private ?\DateTimeInterface $dateSignalement = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateResolution = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $typeIntervention = null;

    #[ORM\Column(nullable: true)]
    private ?int $intervenantId = null;
    
#[ORM\ManyToOne(targetEntity: Equipement::class, inversedBy: 'pannes')]
#[ORM\JoinColumn(nullable: false)]
#[Assert\NotNull(message: "Un équipement doit être sélectionné.")]
private ?Equipement $equipement = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function __toString(): string
    {
        return $this->getDescription() ?? 'Panne';
    }
    // GETTERS ET SETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeInventaire(): ?string
    {
        return $this->codeInventaire;
    }

    public function setCodeInventaire(string $codeInventaire): static
    {
        $this->codeInventaire = $codeInventaire;
        return $this;
    }

    public function getDateSignalement(): ?\DateTimeInterface
    {
        return $this->dateSignalement;
    }

    public function setDateSignalement(\DateTimeInterface $dateSignalement): static
    {
        $this->dateSignalement = $dateSignalement;
        return $this;
    }


    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateResolution(): ?\DateTimeInterface
    {
        return $this->dateResolution;
    }

    public function setDateResolution(?\DateTimeInterface $dateResolution): static
    {
        $this->dateResolution = $dateResolution;
        return $this;
    }

    public function getTypeIntervention(): ?string
    {
        return $this->typeIntervention;
    }

    public function setTypeIntervention(?string $typeIntervention): static
    {
        $this->typeIntervention = $typeIntervention;
        return $this;
    }

    public function getIntervenantId(): ?int
    {
        return $this->intervenantId;
    }

    public function setIntervenantId(?int $intervenantId): static
    {
        $this->intervenantId = $intervenantId;
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

}