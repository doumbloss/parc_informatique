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


    #[ORM\Column(length: 100)]
    private ?string $responsable = null;

    #[ORM\OneToMany(mappedBy: 'localisation', targetEntity: Equipement::class)]
    private Collection $equipements;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)] // Ajout de dateAjout
    private ?\DateTimeImmutable $dateAjout = null;


    public function __construct()
    {
        $this->equipements = new ArrayCollection();
        $this->dateAjout = new \DateTimeImmutable(); // Initialisation par défaut
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


    public function getDateAjout(): ?\DateTimeImmutable
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeImmutable $dateAjout): self
    {
        $this->dateAjout = $dateAjout;
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
        // return sprintf('%s - %s', $this->nomBatiment ?? 'Inconnu', $this->salle ?? 'Inconnu');
        // return sprintf('%s - %s - %s', $this->nomBatiment, $this->etage, $this->salle);
        return ($this->nomBatiment ?? 'Sans nom') . ' - ' . ($this->salle ?? 'Sans salle');
    }

    // public function getNom(): ?string
    // {
    //     // Obtenir la stack trace pour voir d'où vient l'appel
    //     $trace = debug_backtrace();
    //     $caller = $trace[1] ?? null;
        
    //     if ($caller) {
    //         $file = $caller['file'] ?? 'inconnu';
    //         $line = $caller['line'] ?? 'inconnue';
    //         $function = $caller['function'] ?? 'inconnue';
            
    //         // Afficher directement dans la page (temporaire pour debug)
    //         echo "<div style='background:red;color:white;padding:10px;margin:10px;'>
    //                 DEBUG: Accès à getNom() depuis : $file:$line dans la fonction $function
    //             </div>";
    //     }
        
    //     return $this->nomBatiment;
    // }

    // public function getNom(): ?string
    // {
    //     // Log pour voir d'où vient l'appel
    //     trigger_error('Accès à getNom() détecté', E_USER_NOTICE);
    //     return $this->nomBatiment;
    // }

    public function getNom(): ?string
    {
        // Obtenir la stack trace pour voir d'où vient l'appel
        $trace = debug_backtrace();
        $caller = $trace[1] ?? null;
        
        if ($caller) {
            $file = $caller['file'] ?? 'inconnu';
            $line = $caller['line'] ?? 'inconnue';
            $function = $caller['function'] ?? 'inconnue';
            
            error_log("Accès à getNom() depuis : $file:$line dans la fonction $function");
        }
        
        return $this->nomBatiment;
    }
}
