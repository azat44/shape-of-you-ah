<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\OutfitHistoryRepository;

#[ORM\Entity(repositoryClass: OutfitHistoryRepository::class)]
class OutfitHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;
    
    #[ORM\Column(type: 'json', nullable: true)]
    private array $outfitItems = [];
    
    #[ORM\Column(type: 'boolean')]
    private bool $isShared = false;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;
    
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $price = null;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $title = null;
    
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $style = null;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;
    
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
    
    public function getId(): ?int
    {
        return $this->id;
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
    
    public function getOutfitItems(): array
    {
        return $this->outfitItems;
    }
    
    public function setOutfitItems(array $outfitItems): self
    {
        $this->outfitItems = $outfitItems;
        return $this;
    }
    
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    
    // Ajouter la méthode manquante
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    public function isShared(): bool
    {
        return $this->isShared;
    }
    
    public function setIsShared(bool $isShared): self
    {
        $this->isShared = $isShared;
        return $this;
    }
        
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }
    
    public function getStyle(): ?string
    {
        return $this->style;
    }
    
    public function setStyle(?string $style): self
    {
        $this->style = $style;
        return $this;
    }
    
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }
    
    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }
    
    public function getPrice(): ?float
    {
        return $this->price;
    }
    
    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }
        
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user->getNom(),
            'items' => $this->outfitItems,
            'description' => $this->description,
            'title' => $this->title,
            'style' => $this->style,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'is_shared' => $this->isShared,
            'image_url' => $this->imageUrl,
            'price' => $this->price
        ];
    }
}