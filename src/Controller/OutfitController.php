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

/**
 * Contrôleur pour la gestion des tenues
 * Regroupe les fonctionnalités de OutfitController et OutfitSearchController
 */
#[Route('/outfits')]
#[IsGranted('ROLE_USER')]
class OutfitController extends AbstractController 
{
    private EntityManagerInterface $entityManager;
    private OutfitRepository $outfitRepository;
    private OutfitHistoryRepository $outfitHistoryRepository;
    private ClothingItemRepository $clothingItemRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        OutfitRepository $outfitRepository,
        OutfitHistoryRepository $outfitHistoryRepository,
        ClothingItemRepository $clothingItemRepository
    ) {
        $this->entityManager = $entityManager;
        $this->outfitRepository = $outfitRepository;
        $this->outfitHistoryRepository = $outfitHistoryRepository;
        $this->clothingItemRepository = $clothingItemRepository;
    }

    /**
     * Affiche l'historique des tenues
     */
    #[Route('/history', name: 'app_outfit_history', methods: ['GET'])]
    public function showOutfitHistory(): Response 
    {
        $user = $this->getUser();
        $outfitHistoryItems = $this->outfitHistoryRepository->findByUser($user);
        $outfitItems = $this->outfitRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $combinedOutfits = [];
        
        foreach ($outfitHistoryItems as $outfit) {
            $combinedOutfits[] = [
                'id' => $outfit->getId(),
                'type' => 'history',
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle() ?: 'Urban Casual Weekend',
                'image_url' => $outfit->getImageUrl() ?: '/images/jean-bleu.webp',
                'price' => $outfit->getPrice()
            ];
        }
        
        foreach ($outfitItems as $outfit) {
            $imageUrl = null;
            if ($outfit->getClothingItems()->count() > 0) {
                $firstItem = $outfit->getClothingItems()->first();
                if (method_exists($firstItem, 'getImageUrl')) {
                    $imageUrl = $firstItem->getImageUrl();
                }
            }
            
            $combinedOutfits[] = [
                'id' => $outfit->getId(),
                'type' => 'outfit',
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription() ?: 'Description de la tenue...',
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle() ?: 'Urban Casual Weekend',
                'image_url' => $imageUrl ?: '/images/jean-bleu.webp',
                'price' => null 
            ];
        }
        
        usort($combinedOutfits, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $this->render('outfit/history.html.twig', [
            'outfits' => $combinedOutfits
        ]);
    }

    /**
     * Affiche les détails d'une tenue de l'historique
     */
    #[Route('/history/{id}', name: 'app_outfit_history_details', methods: ['GET'])]
    public function showOutfitHistoryDetails(int $id): Response 
    {
        $user = $this->getUser();
        $outfit = $this->outfitHistoryRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$outfit || $id <= 3) {
            $demoData = [
                1 => [
                    'id' => 1,
                    'title' => 'Urban Casual Weekend',
                    'description' => 'Pour vos journées décontractées, cette tenue casual offre un parfait équilibre entre confort et style...',
                    'created_at' => '2023-04-03 17:01:00',
                    'style' => 'Urban Casual Weekend',
                    'image_url' => '/images/jean-bleu.webp',
                    'items' => [
                        ['name' => 'Sweat à capuche gris', 'image_url' => '/images/sweat-capuche-gris.webp'],
                        ['name' => 'Jean bleu', 'image_url' => '/images/jean-bleu.webp'],
                        ['name' => 'Baskets noires', 'image_url' => '/images/basket-noir.jpg']
                    ]
                ],
            ];
            
            return $this->render('outfit/details.html.twig', [
                'outfit' => $demoData[$id] ?? $demoData[1]
            ]);
        }
        
        // Préparer les données de la tenue
        $outfitArray = [
            'id' => $outfit->getId(),
            'type' => 'history',
            'title' => $outfit->getTitle() ?: 'Tenue sans titre',
            'description' => $outfit->getDescription(),
            'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
            'style' => $outfit->getStyle() ?: 'Urban Casual Weekend',
            'image_url' => $outfit->getImageUrl() ?: '/images/jean-bleu.webp',
            'price' => $outfit->getPrice(),
            'items' => $outfit->getOutfitItems() ?: [
                ['name' => 'Élément par défaut', 'image_url' => '/images/placeholder.jpg']
            ]
        ];
        
        return $this->render('outfit/details.html.twig', [
            'outfit' => $outfitArray
        ]);
    }

    /**
     * Affiche les détails d'une tenue
     */
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
                'category' => method_exists($item, 'getCategory') ? $item->getCategory()->getName() : null,
                'image_url' => method_exists($item, 'getImageUrl') ? $item->getImageUrl() : null,
            ];
        }
        
        $outfitArray = [
            'id' => $outfit->getId(),
            'type' => 'outfit',
            'title' => $outfit->getTitle() ?: 'Tenue sans titre',
            'description' => $outfit->getDescription(),
            'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
            'style' => $outfit->getStyle() ?: 'Urban Casual Weekend',
            'items' => $items,
            'price' => null 
        ];
        
        return $this->render('outfit/details.html.twig', [
            'outfit' => $outfitArray
        ]);
    }

    /**
     * Liste des tenues partagées
     */
    #[Route('/social', name: 'app_outfit_social', methods: ['GET'])]
    public function showSharedOutfits(): Response 
    {
        $sharedOutfits = $this->outfitHistoryRepository->findSharedOutfits();
        return $this->render('social.html.twig', [
            'outfits' => $sharedOutfits
        ]);
    }

    /**
     * Partager une tenue
     */
    #[Route('/{id}/share', name: 'app_outfit_share', methods: ['POST'])]
    public function shareOutfit(OutfitHistory $outfit): JsonResponse 
    {
        if ($outfit->getUser() !== $this->getUser()) {
            return $this->json(['status' => 'error', 'message' => 'Vous ne pouvez pas partager cette tenue'], 403);
        }
        $outfit->setShared(true);
        $this->entityManager->flush();
        return $this->json([
            'status' => 'success',
            'message' => 'Tenue partagée avec succès',
            'outfit' => [
                'id' => $outfit->getId(),
                'is_shared' => $outfit->isShared()
            ]
        ]);
    }

    /**
     * Sauvegarder une tenue dans l'historique
     */
    #[Route('/history/save', name: 'app_outfit_history_save', methods: ['POST'])]
    public function saveOutfitHistory(Request $request): JsonResponse 
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $outfitHistory = new OutfitHistory();
        $outfitHistory->setUser($user);
        $outfitHistory->setCreatedAt(new \DateTime());
        $outfitItems = $data['outfitItems'] ?? $data['items'] ?? [];
        $outfitHistory->setOutfitItems($outfitItems);
        
        if (isset($data['description'])) {
            $outfitHistory->setDescription($data['description']);
        }
        if (isset($data['image_url'])) {
            $outfitHistory->setImageUrl($data['image_url']);
        }
        if (isset($data['price'])) {
            $outfitHistory->setPrice($data['price']);
        }
        if (isset($data['style'])) {
            $outfitHistory->setStyle($data['style']);
        }
        if (isset($data['title'])) {
            $outfitHistory->setTitle($data['title']);
        }
    
        $this->entityManager->persist($outfitHistory);
        $this->entityManager->flush();
        return $this->json([
            'status' => 'success',
            'message' => 'Tenue ajoutée à l\'historique',
            'outfit_id' => $outfitHistory->getId()
        ]);
    }
    
    /**
     * Enregistrer une nouvelle tenue
     */
    #[Route('/save', name: 'app_outfit_save', methods: ['POST'])]
    public function saveOutfit(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['itemIds']) || !is_array($data['itemIds']) || empty($data['itemIds'])) {
            return $this->json(['error' => 'Données invalides', 'received' => $data], Response::HTTP_BAD_REQUEST);
        }

        $title = $data['title'];
        $itemIds = $data['itemIds'];
        $description = $data['description'] ?? '';
        $style = $data['style'] ?? 'casual';

        $outfit = new Outfit();
        $outfit->setTitle($title);
        $outfit->setUser($user);
        $outfit->setCreatedAt(new \DateTime());
        $outfit->setDescription($description);
        $outfit->setStyle($style);
        
        $validItems = [];
        foreach ($itemIds as $itemId) {
            $item = $this->clothingItemRepository->find($itemId);
            if ($item) {
                $outfit->addClothingItem($item);
                $validItems[] = $item;
            }
        }

        if (empty($validItems)) {
            return $this->json(['error' => 'Aucun article valide trouvé', 'itemIds' => $itemIds], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($outfit);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tenue enregistrée avec succès',
            'outfitId' => $outfit->getId(),
            'itemCount' => count($validItems)
        ], Response::HTTP_CREATED);
    }
    
    /**
     * Supprimer une tenue
     */
    #[Route('/delete/{id}', name: 'app_outfit_delete', methods: ['DELETE'])]
    public function deleteOutfit(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        $outfit = $this->outfitRepository->findOneBy([
            'id' => $id,
            'user' => $user
        ]);
        
        if (!$outfit) {
            return $this->json(['error' => 'Tenue non trouvée'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($outfit);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Tenue supprimée avec succès'
        ]);
    }
    
    /**
     * Recherche de tenues avec filtres
     */
    #[Route('/search', name: 'app_outfit_search', methods: ['GET'])]
    public function search(Request $request): Response 
    {
        $user = $this->getUser();
        $searchQuery = $request->query->get('q');
        $style = $request->query->get('style');
        $period = $request->query->get('period');
        $shared = $request->query->get('shared');
        
        $fromDate = null;
        if ($period) {
            $today = new \DateTime();
            $fromDate = clone $today;
            
            switch ($period) {
                case 'week': $fromDate->modify('-1 week'); break;
                case 'month': $fromDate->modify('-1 month'); break;
                case 'year': $fromDate->modify('-1 year'); break;
            }
        }
        
        $outfitHistoryItems = $this->outfitHistoryRepository->findByUser($user);
        $outfitItems = $this->outfitRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        if ($shared === 'shared') {
            $outfitHistoryItems = array_filter($outfitHistoryItems, function($outfit) {
                return $outfit->isShared();
            });
            $outfitItems = []; 
        }
        elseif ($shared === 'personal') {
            $outfitHistoryItems = array_filter($outfitHistoryItems, function($outfit) {
                return !$outfit->isShared();
            });
        }
        
        $combinedOutfits = [];
        
        // Filtrer les tenues de l'historique
        foreach ($outfitHistoryItems as $outfit) {
            if ($fromDate && $outfit->getCreatedAt() < $fromDate) {
                continue;
            }
            
            if ($style && $outfit->getStyle() !== $style) {
                continue;
            }
            
            if ($searchQuery) {
                $title = strtolower($outfit->getTitle() ?? '');
                $description = strtolower($outfit->getDescription() ?? '');
                $searchTerm = strtolower($searchQuery);
                
                if (strpos($title, $searchTerm) === false && strpos($description, $searchTerm) === false) {
                    continue;
                }
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
        
        // Filtrer les tenues régulières
        foreach ($outfitItems as $outfit) {
            if ($fromDate && $outfit->getCreatedAt() < $fromDate) {
                continue;
            }
            
            if ($style && $outfit->getStyle() !== $style) {
                continue;
            }
            
            if ($searchQuery) {
                $title = strtolower($outfit->getTitle() ?? '');
                $description = strtolower($outfit->getDescription() ?? '');
                $searchTerm = strtolower($searchQuery);
                
                if (strpos($title, $searchTerm) === false && strpos($description, $searchTerm) === false) {
                    continue;
                }
            }
            
            $imageUrl = null;
            if ($outfit->getClothingItems()->count() > 0) {
                $firstItem = $outfit->getClothingItems()->first();
                if (method_exists($firstItem, 'getImageUrl')) {
                    $imageUrl = $firstItem->getImageUrl();
                }
            }
            
            $combinedOutfits[] = [
                'id' => $outfit->getId(),
                'type' => 'outfit',
                'title' => $outfit->getTitle(),
                'description' => $outfit->getDescription(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'style' => $outfit->getStyle(),
                'image_url' => $imageUrl,
                'price' => null,
                'is_shared' => false
            ];
        }
        
        usort($combinedOutfits, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $this->render('outfit/search.html.twig', [
            'outfits' => $combinedOutfits,
            'searchQuery' => $searchQuery,
            'filters' => [
                'style' => $style,
                'period' => $period,
                'shared' => $shared
            ]
        ]);
    }
}