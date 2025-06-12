<?php

namespace App\Entity;

use App\Entity\Equipement;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private $id;
    

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $fonction = null;

    #[ORM\Column(length: 100)]
    private ?string $service = null;

    #[ORM\Column(length: 100)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    private ?string $telephone = null;

    #[ORM\Column(length: 100)]
    private ?string $role = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Equipement::class)]
    private \Doctrine\Common\Collections\Collection $equipements;

    #[ORM\OneToOne(mappedBy: 'utilisateur', targetEntity: User::class, cascade: ['persist', 'remove'])]
    private ?User $user = null;

   public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        // Éviter les boucles infinies
        if ($user !== $this->user) {
            $this->user = $user;
            if ($user !== null && $user->getUtilisateur() !== $this) {
                $user->setUtilisateur($this);
            }
        }
        return $this;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        // Synchroniser avec User si nécessaire
        if ($this->user && $role === 'admin') {
            $currentRoles = $this->user->getRoles();
            if (!in_array('ROLE_ADMIN', $currentRoles)) {
                $currentRoles[] = 'ROLE_ADMIN';
                $this->user->setRoles(array_unique($currentRoles));
            }
        } elseif ($this->user) {
            $currentRoles = $this->user->getRoles();
            $roles = array_diff($currentRoles, ['ROLE_ADMIN']);
            $this->user->setRoles(array_unique($roles));
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Utilisateur non défini';  // Vous pouvez choisir d'afficher d'autres propriétés si nécessaire
    }

  
// public function __toString(): string
// {
//     return $this->nom . ' (' . $this->role . ')';
// }


      
}
