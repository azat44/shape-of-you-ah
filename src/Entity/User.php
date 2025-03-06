<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà utilisé')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password = '';
    
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserWardrobe::class, cascade: ['persist', 'remove'])]
    private Collection $wardrobeItems;
    
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Outfit::class, cascade: ['persist', 'remove'])]
    private Collection $outfits;
    
    // Ajout du champ createdAt
    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;
    
    public function __construct()
    {
        $this->wardrobeItems = new ArrayCollection();
        $this->outfits = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getter pour createdAt
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Setter pour createdAt
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
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

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
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

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    private ?string $plainPassword = null;

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }
    
    public function getNom(): ?string
    {
        return $this->username; // Utiliser username comme nom
    }
    
    public function setNom(?string $nom): self
    {
        // Ne fait rien, pour maintenir la compatibilité
        return $this;
    }
    
    /**
     * @return Collection<int, UserWardrobe>
     */
    public function getWardrobeItems(): Collection
    {
        return $this->wardrobeItems;
    }
    
    public function addWardrobeItem(UserWardrobe $wardrobeItem): self
    {
        if (!$this->wardrobeItems->contains($wardrobeItem)) {
            $this->wardrobeItems->add($wardrobeItem);
            $wardrobeItem->setUser($this);
        }
        
        return $this;
    }
    
    public function removeWardrobeItem(UserWardrobe $wardrobeItem): self
    {
        if ($this->wardrobeItems->removeElement($wardrobeItem)) {
            // set the owning side to null (unless already changed)
            if ($wardrobeItem->getUser() === $this) {
                $wardrobeItem->setUser(null);
            }
        }
        
        return $this;
    }
    
    public function hasItemInWardrobe(ClothingItem $clothingItem): bool
    {
        foreach ($this->wardrobeItems as $wardrobeItem) {
            if ($wardrobeItem->getClothingItem() === $clothingItem) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getWardrobeItem(ClothingItem $clothingItem): ?UserWardrobe
    {
        foreach ($this->wardrobeItems as $wardrobeItem) {
            if ($wardrobeItem->getClothingItem() === $clothingItem) {
                return $wardrobeItem;
            }
        }
        
        return null;
    }
    
    /**
     * @return Collection<int, Outfit>
     */
    public function getOutfits(): Collection
    {
        return $this->outfits;
    }
    
    public function addOutfit(Outfit $outfit): self
    {
        if (!$this->outfits->contains($outfit)) {
            $this->outfits->add($outfit);
            $outfit->setUser($this);
        }
        
        return $this;
    }
    
    public function removeOutfit(Outfit $outfit): self
    {
        if ($this->outfits->removeElement($outfit)) {
            if ($outfit->getUser() === $this) {
                $outfit->setUser(null);
            }
        }
        
        return $this;
    }
}