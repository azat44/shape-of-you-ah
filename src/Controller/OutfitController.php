<?php
namespace App\Controller;

use App\Entity\Outfit;
use App\Entity\OutfitHistory;
use App\Repository\OutfitHistoryRepository;
use App\Repository\OutfitRepository;
use App\Repository\ClothingItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/outfits')]
#[IsGranted('ROLE_USER')]
class OutfitController extends AbstractController 
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OutfitRepository $outfitRepository,
        private OutfitHistoryRepository $outfitHistoryRepository,
        private ClothingItemRepository $clothingItemRepository
    ) {}

    #[Route('/history', name: 'app_outfit_history', methods: ['GET'])]
    public function showOutfitHistory(): Response 
    {
        $user = $this->getUser();
        
        // Récupérer les vêtements de la garde-robe et les tenues
        $userClothingItems = [];
        foreach ($user->getWardrobeItems() as $item) {
            $userClothingItems[] = $item->getClothingItem();
        }
        
        $outfitHistoryItems = $this->outfitHistoryRepository->findByUser($user);
        $outfitItems = $this->outfitRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $combinedOutfits = [];
        
        // Ajouter les tenues de l'historique
        foreach ($outfitHistoryItems as $outfit) {
            $combinedOutfits[] = [
                'id' => $outfit->getId(),
                'type' => 'history',
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle() ?: 'Casual',
                'image_url' => $outfit->getImageUrl() ?: '/images/placeholder.jpg',
                'price' => $outfit->getPrice(),
                'is_shared' => $outfit->isShared()
            ];
        }
        
        // Ajouter les tenues régulières
        foreach ($outfitItems as $outfit) {
            $imageUrl = null;
            if ($outfit->getClothingItems()->count() > 0) {
                $imageUrl = $outfit->getClothingItems()->first()?->getImageUrl();
            }
            
            $combinedOutfits[] = [
                'id' => $outfit->getId(),
                'type' => 'outfit',
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription() ?: 'Description...',
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle() ?: 'Casual',
                'image_url' => $imageUrl ?: '/images/placeholder.jpg',
                'price' => null 
            ];
        }
        
        usort($combinedOutfits, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        
        return $this->render('outfit/history.html.twig', [
            'outfits' => $combinedOutfits,
            'userClothingItems' => $userClothingItems
        ]);
    }

    #[Route('/history/{id}', name: 'app_outfit_history_details', methods: ['GET'])]
    public function showOutfitHistoryDetails(int $id): Response 
    {
        $user = $this->getUser();
        $outfit = $this->outfitHistoryRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$outfit && $id <= 3) {
            // Utiliser des données démo pour les ID 1-3
            $demoItems = [
                1 => ['Urban Casual Weekend', '/images/jean-bleu.webp', 'casual'],
                2 => ['Tenue de soirée', '/images/veste-velour.webp', 'soirée'],
                3 => ['Sport & Détente', '/images/tshirt-technique.jpg', 'sport']
            ];
            
            return $this->render('outfit/details.html.twig', [
                'outfit' => [
                    'id' => $id,
                    'title' => $demoItems[$id][0],
                    'description' => 'Description de la tenue démo...',
                    'created_at' => '2023-01-01 12:00:00',
                    'style' => $demoItems[$id][2],
                    'image_url' => $demoItems[$id][1],
                    'items' => [
                        ['name' => 'Item 1', 'image_url' => '/images/placeholder.jpg'],
                        ['name' => 'Item 2', 'image_url' => '/images/placeholder.jpg']
                    ]
                ]
            ]);
        } elseif (!$outfit) {
            throw $this->createNotFoundException('Tenue non trouvée');
        }
        
        return $this->render('outfit/details.html.twig', [
            'outfit' => [
                'id' => $outfit->getId(),
                'type' => 'history',
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle() ?: 'Casual',
                'image_url' => $outfit->getImageUrl() ?: '/images/placeholder.jpg',
                'price' => $outfit->getPrice(),
                'is_shared' => $outfit->isShared(),
                'items' => $outfit->getOutfitItems() ?: [['name' => 'Item par défaut', 'image_url' => '/images/placeholder.jpg']]
            ]
        ]);
    }

    #[Route('/outfit/{id}', name: 'app_outfit_details', methods: ['GET'])]
    public function showOutfitDetails(int $id): Response 
    {
        $user = $this->getUser();
        $outfit = $this->outfitRepository->findOneBy(['id' => $id, 'user' => $user]);
            
        if (!$outfit) {
            throw $this->createNotFoundException('Tenue non trouvée');
        }
        
        $items = [];
        foreach ($outfit->getClothingItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'category' => $item->getCategory()->getName(),
                'image_url' => $item->getImageUrl(),
                'price' => $item->getPrice()
            ];
        }
        
        $totalPrice = array_sum(array_filter(array_map(fn($item) => $item['price'] ?: 0, $items)));
        
        $imageUrl = '/images/placeholder.jpg';  
        if (count($items) > 0 && isset($items[0]['image_url'])) {
            $imageUrl = $items[0]['image_url'];
        }
        
        return $this->render('outfit/details.html.twig', [
            'outfit' => [
                'id' => $outfit->getId(),
                'type' => 'outfit',
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle() ?: 'Casual',
                'items' => $items,
                'price' => $totalPrice,
                'image_url' => $imageUrl 
            ]
        ]);
    }

    #[Route('/social', name: 'app_outfit_social', methods: ['GET'])]
    public function showSharedOutfits(): Response 
    {
        $sharedOutfits = $this->outfitHistoryRepository->findSharedOutfits();
        return $this->render('social.html.twig', ['outfits' => $sharedOutfits]);
    }

    #[Route('/{id}/share', name: 'app_outfit_share', methods: ['POST'])]
    public function shareOutfit(OutfitHistory $outfit): JsonResponse 
    {
        if ($outfit->getUser() !== $this->getUser()) {
            return $this->json(['status' => 'error', 'message' => 'Action non autorisée'], 403);
        }
        
        $outfit->setIsShared(!$outfit->isShared());
        $this->entityManager->flush();
        
        return $this->json([
            'status' => 'success',
            'message' => 'Statut de partage mis à jour',
            'outfit' => ['id' => $outfit->getId(), 'is_shared' => $outfit->isShared()]
        ]);
    }

    #[Route('/save', name: 'app_outfit_save', methods: ['POST'])]
    public function saveOutfit(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'], $data['itemIds']) || !is_array($data['itemIds']) || empty($data['itemIds'])) {
            return $this->json(['error' => 'Données incomplètes'], Response::HTTP_BAD_REQUEST);
        }

        $outfit = new Outfit();
        $outfit->setTitle($data['title']);
        $outfit->setUser($user);
        $outfit->setCreatedAt(new \DateTime());
        $outfit->setDescription($data['description'] ?? '');
        $outfit->setStyle($data['style'] ?? 'casual');
        
        // Ajouter les vêtements de la garde-robe
        $validItems = [];
        foreach ($data['itemIds'] as $itemId) {
            $item = $this->clothingItemRepository->find($itemId);
            if ($item && $user->hasItemInWardrobe($item)) {
                $outfit->addClothingItem($item);
                $validItems[] = $item;
            }
        }

        if (empty($validItems)) {
            return $this->json(['error' => 'Aucun article valide trouvé'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($outfit);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tenue enregistrée',
            'outfitId' => $outfit->getId(),
            'itemCount' => count($validItems)
        ], Response::HTTP_CREATED);
    }
    
    #[Route('/history/save', name: 'app_outfit_history_save', methods: ['POST'])]
    public function saveOutfitHistory(Request $request): JsonResponse 
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        
        $outfitHistory = new OutfitHistory();
        $outfitHistory->setUser($user);
        $outfitHistory->setCreatedAt(new \DateTime());
        
        if (isset($data['itemIds']) && is_array($data['itemIds'])) {
            $itemsData = [];
            foreach ($data['itemIds'] as $itemId) {
                $item = $this->clothingItemRepository->find($itemId);
                if ($item && $user->hasItemInWardrobe($item)) {
                    $itemsData[] = [
                        'id' => $item->getId(),
                        'name' => $item->getName(),
                        'category' => $item->getCategory()->getName(),
                        'image_url' => $item->getImageUrl()
                    ];
                }
            }
            $outfitHistory->setOutfitItems($itemsData);
        } else {
            $outfitHistory->setOutfitItems($data['items'] ?? []);
        }
        
        // Autres données
        if (isset($data['title'])) $outfitHistory->setTitle($data['title']);
        if (isset($data['description'])) $outfitHistory->setDescription($data['description']);
        if (isset($data['style'])) $outfitHistory->setStyle($data['style']);
        if (isset($data['image_url'])) $outfitHistory->setImageUrl($data['image_url']);
        if (isset($data['price'])) $outfitHistory->setPrice($data['price']);
    
        $this->entityManager->persist($outfitHistory);
        $this->entityManager->flush();
        
        return $this->json([
            'status' => 'success',
            'message' => 'Tenue sauvegardée',
            'outfit_id' => $outfitHistory->getId()
        ]);
    }
    
    #[Route('/delete/{id}', name: 'app_outfit_delete', methods: ['DELETE'])]
    public function deleteOutfit(int $id): JsonResponse
    {
        $user = $this->getUser();
        $outfit = $this->outfitRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$outfit) {
            return $this->json(['error' => 'Tenue non trouvée'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($outfit);
        $this->entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Tenue supprimée']);
    }
    
    #[Route('/search', name: 'app_outfit_search', methods: ['GET'])]
    public function search(Request $request): Response 
    {
        $user = $this->getUser();
        $searchQuery = $request->query->get('q');
        $style = $request->query->get('style');
        $period = $request->query->get('period');
        $shared = $request->query->get('shared');
        
        // Déterminer la date limite selon la période
        $fromDate = null;
        if ($period) {
            $fromDate = new \DateTime();
            $fromDate->modify('-1 ' . ($period === 'week' ? 'week' : ($period === 'month' ? 'month' : 'year')));
        }
        
        // Récupérer les tenues
        $outfitHistoryItems = $this->outfitHistoryRepository->findByUser($user);
        $outfitItems = $this->outfitRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $combinedOutfits = [];
        
        // Filtrer par type de partage
        if ($shared === 'shared') {
            $outfitHistoryItems = array_filter($outfitHistoryItems, fn($o) => $o->isShared());
            $outfitItems = []; 
        } elseif ($shared === 'personal') {
            $outfitHistoryItems = array_filter($outfitHistoryItems, fn($o) => !$o->isShared());
        }
        
        // Traiter les tenues historiques
        foreach ($outfitHistoryItems as $outfit) {
            // Filtrer par date, style et terme de recherche
            if (($fromDate && $outfit->getCreatedAt() < $fromDate) ||
                ($style && $outfit->getStyle() !== $style) ||
                ($searchQuery && !$this->matchesSearch($outfit, $searchQuery))) {
                continue;
            }
            
            $combinedOutfits[] = [
                'id' => $outfit->getId(),
                'type' => 'history',
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle(),
                'image_url' => $outfit->getImageUrl(),
                'price' => $outfit->getPrice(),
                'is_shared' => $outfit->isShared()
            ];
        }
        
        // Traiter les tenues régulières
        foreach ($outfitItems as $outfit) {
            // Filtrer par date, style et terme de recherche
            if (($fromDate && $outfit->getCreatedAt() < $fromDate) ||
                ($style && $outfit->getStyle() !== $style) ||
                ($searchQuery && !$this->matchesSearch($outfit, $searchQuery))) {
                continue;
            }
            
            $combinedOutfits[] = [
                'id' => $outfit->getId(),
                'type' => 'outfit',
                'title' => $outfit->getTitle(),
                'description' => $outfit->getDescription(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle(),
                'image_url' => $outfit->getClothingItems()->first()?->getImageUrl(),
                'price' => null,
                'is_shared' => false
            ];
        }
        
        usort($combinedOutfits, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        
        return $this->render('outfit/search.html.twig', [
            'outfits' => $combinedOutfits,
            'searchQuery' => $searchQuery,
            'filters' => ['style' => $style, 'period' => $period, 'shared' => $shared]
        ]);
    }
    
    private function matchesSearch($outfit, string $query): bool
    {
        $title = strtolower($outfit->getTitle() ?? '');
        $desc = strtolower($outfit->getDescription() ?? '');
        $term = strtolower($query);
        
        return strpos($title, $term) !== false || strpos($desc, $term) !== false;
    }
}