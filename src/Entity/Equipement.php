<?php

namespace App\Entity;

use App\Entity\Utilisateur;
use App\Entity\Localisation;
use App\Entity\Panne;
use App\Entity\Licence;
use App\Entity\Historique;
use App\Repository\EquipementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipementRepository::class)]
class Equipement
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    #[ORM\Column(length: 100, unique: true)]
    // #[Assert\NotBlank(message: "Le code d'inventaire est obligatoire.")]
    private ?string $codeInventaire = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $marque = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $modele = null;

    #[ORM\Column(length: 100)]
    private ?string $numeroSerie = null;

    #[ORM\Column(length: 100)]
    private ?string $categorie = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateAchat = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $garantieFin = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: 'text')]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'equipements')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Localisation::class, inversedBy: 'equipements')]
    private ?Localisation $localisation = null;

    #[ORM\OneToMany(mappedBy: 'equipement', targetEntity: Panne::class, cascade: ['remove'])]
    private Collection $pannes;

    #[ORM\ManyToMany(targetEntity: Licence::class, inversedBy: 'equipements')]
    private Collection $licences;

    #[ORM\OneToMany(mappedBy: 'equipement', targetEntity: Historique::class)]
    private Collection $historiques;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateAjout = null;

    public function __construct()
    {
        $this->pannes = new ArrayCollection();
        $this->licences = new ArrayCollection();
        $this->historiques = new ArrayCollection();
        $this->dateAjout = new \DateTimeImmutable();
        // Ne pas appeler generateUniqueCodeInventaire() ici
    }

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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): static
    {
        $this->marque = $marque;
        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;
        return $this;
    }

    public function getNumeroSerie(): ?string
    {
        return $this->numeroSerie;
    }

    public function setNumeroSerie(string $numeroSerie): static
    {
        $this->numeroSerie = $numeroSerie;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getDateAchat(): ?\DateTimeInterface
    {
        return $this->dateAchat;
    }

    public function setDateAchat(\DateTimeInterface $dateAchat): static
    {
        $this->dateAchat = $dateAchat;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getGarantieFin(): ?\DateTimeInterface
    {
        return $this->garantieFin;
    }

    public function setGarantieFin(\DateTimeInterface $garantieFin): static
    {
        $this->garantieFin = $garantieFin;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getLocalisation(): ?Localisation
    {
        return $this->localisation;
    }

    public function setLocalisation(?Localisation $localisation): static
    {
        $this->localisation = $localisation;
        return $this;
    }

    /**
     * @return Collection<int, Panne>
     */
    public function getPannes(): Collection
    {
        return $this->pannes;
    }

    public function addPanne(Panne $panne): static
    {
        if (!$this->pannes->contains($panne)) {
            $this->pannes->add($panne);
            $panne->setEquipement($this);
        }
        return $this;
    }

    public function removePanne(Panne $panne): static
    {
        if ($this->pannes->removeElement($panne)) {
            if ($panne->getEquipement() === $this) {
                $panne->setEquipement(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Licence>
     */
    public function getLicences(): Collection
    {
        return $this->licences;
    }

    public function addLicence(Licence $licence): static
    {
        if (!$this->licences->contains($licence)) {
            $this->licences->add($licence);
        }
        return $this;
    }

    public function removeLicence(Licence $licence): static
    {
        $this->licences->removeElement($licence);
        return $this;
    }

    /**
     * @return Collection<int, Historique>
     */
    public function getHistoriques(): Collection
    {
        return $this->historiques;
    }

    public function addHistorique(Historique $historique): static
    {
        if (!$this->historiques->contains($historique)) {
            $this->historiques->add($historique);
            $historique->setEquipement($this);
        }
        return $this;
    }

    public function removeHistorique(Historique $historique): static
    {
        if ($this->historiques->removeElement($historique)) {
            if ($historique->getEquipement() === $this) {
                $historique->setEquipement(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->nom;
    }

    /**
     * Génère un code d'inventaire unique au format INV-YYYY-XXXX
     */
    public function generateUniqueCodeInventaire(\Doctrine\ORM\EntityManagerInterface $entityManager): void
    {
        $repository = $entityManager->getRepository(Equipement::class);
        $year = date('Y');
        $prefix = 'INV-' . $year;

        $lastCode = $repository->findOneBy([], ['codeInventaire' => 'DESC']);
        $lastNumber = 0;

        if ($lastCode && strpos($lastCode->getCodeInventaire(), $prefix) === 0) {
            $lastNumber = (int) substr($lastCode->getCodeInventaire(), -4);
        }

        $newNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        $this->codeInventaire = $prefix . '-' . $newNumber;
    }
}