<?php

namespace App\Service;

use App\Entity\AINotification;
use App\Entity\Category;
use App\Repository\AINotificationRepository;
use App\Repository\CategoryRepository;
use App\Repository\ClothingItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class AIAlertService
{
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;
    private ClothingItemRepository $clothingItemRepository;
    private AINotificationRepository $notificationRepository;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository,
        ClothingItemRepository $clothingItemRepository,
        AINotificationRepository $notificationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->categoryRepository = $categoryRepository;
        $this->clothingItemRepository = $clothingItemRepository;
        $this->notificationRepository = $notificationRepository;
    }
    
    public function checkDetectedItems(array $detectedItems): array
    {
        $notifications = [];
        
        foreach ($detectedItems as $item) {
            $itemName = $item['name'] ?? '';
            $itemCategory = $item['category'] ?? '';
            
            $existingItems = $this->clothingItemRepository->findSimilarItems($itemName);
            
            if (count($existingItems) === 0) {
                $notification = new AINotification();
                $notification->setMessage("Nouvel élément détecté : \"$itemName\" qui n'est pas présent dans la base");
                $notification->setType('detection');
                $notification->setStatus('new');
                $notification->setCreatedAt(new \DateTime());
                $notification->setData([
                    'item_name' => $itemName,
                    'item_category' => $itemCategory
                ]);
                
                $this->entityManager->persist($notification);
                $this->entityManager->flush();
                
                $notifications[] = [
                    'id' => $notification->getId(),
                    'message' => $notification->getMessage(),
                    'date' => $notification->getCreatedAt(),
                    'status' => $notification->getStatus(),
                    'type' => $notification->getType()
                ];
            }
        }
        
        return $notifications;
    }
    
    public function getActiveNotifications(): array
    {
        $notifications = $this->notificationRepository->findBy(['status' => ['new', 'pending']], ['createdAt' => 'DESC']);
        
        $formattedNotifications = [];
        foreach ($notifications as $notification) {
            $formattedNotifications[] = [
                'id' => $notification->getId(),
                'message' => $notification->getMessage(),
                'date' => $notification->getCreatedAt(),
                'status' => $notification->getStatus(),
                'type' => $notification->getType(),
                'data' => $notification->getData()
            ];
        }
        
        return $formattedNotifications;
    }
    
    public function resolveNotification(int $id): bool
    {
        $notification = $this->notificationRepository->find($id);
        
        if (!$notification) {
            return false;
        }
        
        $notification->setStatus('resolved');
        $notification->setResolvedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        return true;
    }
}