<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = 'Utilisateur';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Outfit::class, mappedBy: 'user')]
    private Collection $outfits;

    #[ORM\OneToMany(targetEntity: UserWardrobe::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $wardrobeItems;

    public function __construct()
    {
        $this->outfits = new ArrayCollection();
        $this->wardrobeItems = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom ?? 'Utilisateur';
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getOutfits(): Collection
    {
        return $this->outfits;
    }

    public function getWardrobeItems(): Collection
    {
        return $this->wardrobeItems;
    }

    public function hasItemInWardrobe(ClothingItem $item): bool
    {
        foreach ($this->wardrobeItems as $wardrobeItem) {
            if ($wardrobeItem->getClothingItem()->getId() === $item->getId()) {
                return true;
            }
        }
        return false;
    }

    public function getWardrobeItem(ClothingItem $item): ?UserWardrobe
    {
        foreach ($this->wardrobeItems as $wardrobeItem) {
            if ($wardrobeItem->getClothingItem()->getId() === $item->getId()) {
                return $wardrobeItem;
            }
        }
        return null;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }
}