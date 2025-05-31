<?php

namespace App\Entity;

use App\Repository\LocalisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocalisationRepository::class)]
class Localisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private $id ;


    #[ORM\Column(length: 100)]
    private ?string $nomBatiment = null;

     
    #[ORM\Column(length: 100)]
    private ?string $etage = null;

    #[ORM\Column(length: 100)]
    private ?string $salle = null;

    #[ORM\Column(length: 100)]
    private ?string $codeLocal = null;

    // #[ORM\Column(length: 255)]
    // private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $responsable = null;

    #[ORM\OneToMany(mappedBy: 'localisation', targetEntity: Equipement::class)]
    private Collection $equipements;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\ManyToOne(inversedBy: 'localisations')]
    #[ORM\JoinColumn(nullable: true)] // ou false selon ton besoin
    private ?Batiment $batiment = null;

    public function __construct()
    {
        $this->equipements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomBatiment(): ?string
    {
        return $this->nomBatiment;
    }

    public function setNomBatiment(string $nomBatiment): self
    {
        $this->nomBatiment = $nomBatiment;
        return $this;
    }


    // public function getNom(): ?string
    // {
    //     return $this->nom;
    // }

    // public function setNom(string $nom): static
    // {
    //     $this->nom = $nom;
    //     return $this;
    // }

    public function getEtage(): ?string
    {
        return $this->etage;
    }

    public function setEtage(string $etage): self
    {
        $this->etage = $etage;
        return $this;
    }

    public function getSalle(): ?string
    {
        return $this->salle;
    }

    public function setSalle(string $salle): self
    {
        $this->salle = $salle;
        return $this;
    }

    public function getCodeLocal(): ?string
    {
        return $this->codeLocal;
    }

    public function setCodeLocal(string $codeLocal): self
    {
        $this->codeLocal = $codeLocal;
        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): self
    {
        $this->responsable = $responsable;
        return $this;
    }

    /**
     * @return Collection<int, Equipement>
     */
    public function getEquipements(): Collection
    {
        return $this->equipements;
    }

    public function addEquipement(Equipement $equipement): self
    {
        if (!$this->equipements->contains($equipement)) {
            $this->equipements[] = $equipement;
            $equipement->setLocalisation($this);
        }

        return $this;
    }

    public function removeEquipement(Equipement $equipement): self
    {
        if ($this->equipements->removeElement($equipement)) {
            if ($equipement->getLocalisation() === $this) {
                $equipement->setLocalisation(null);
            }
        }

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function __toString(): string
    {
        return $this->nomBatiment . ' - ' . $this->salle;
        return $this->nom ?? 'Localisation';
    }


    public function getBatiment(): ?Batiment
{
    return $this->batiment;
}

public function setBatiment(?Batiment $batiment): self
{
    $this->batiment = $batiment;
    return $this;
}

}
