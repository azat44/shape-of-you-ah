<?php
namespace App\Controller\Admin;

use App\Service\AdminAIService;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\ClothingItemRepository;
use App\Repository\OutfitRepository;
use App\Repository\OutfitHistoryRepository;
use App\Repository\AINotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private CategoryRepository $categoryRepository;
    private ClothingItemRepository $clothingItemRepository;
    private OutfitRepository $outfitRepository;
    private OutfitHistoryRepository $outfitHistoryRepository;
    private AdminAIService $adminAIService;
    private AINotificationRepository $notificationRepository;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        ClothingItemRepository $clothingItemRepository,
        OutfitRepository $outfitRepository,
        OutfitHistoryRepository $outfitHistoryRepository,
        AdminAIService $adminAIService,
        AINotificationRepository $notificationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->clothingItemRepository = $clothingItemRepository;
        $this->outfitRepository = $outfitRepository;
        $this->outfitHistoryRepository = $outfitHistoryRepository;
        $this->adminAIService = $adminAIService;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        $totalUsers = count($this->userRepository->findAll());
        $totalItems = count($this->clothingItemRepository->findAll());
        $totalOutfits = count($this->outfitRepository->findAll());
        $totalSharedOutfits = count($this->outfitHistoryRepository->findSharedOutfits());
        
        $adminData = [
            'totalUsers' => $totalUsers,
            'totalItems' => $totalItems,
            'totalOutfits' => $totalOutfits,
            'totalSharedOutfits' => $totalSharedOutfits
        ];
    
        $aiInsights = $this->adminAIService->generateAdminInsights($adminData);
        
        $notifications = $this->notificationRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );
        
        $userGrowthData = $this->getUserGrowthData();
        $outfitStyleData = $this->getOutfitStyleData();
        $categoryData = $this->getCategoryData();
        $activityData = $this->getActivityData();
    
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users' => $totalUsers,
                'items' => $totalItems,
                'outfits' => $totalOutfits,
                'shared_outfits' => $totalSharedOutfits
            ],
            'aiInsights' => $aiInsights['insights'] ?? 'Aucun insight disponible',
            'notifications' => $notifications,
            'userGrowthData' => $userGrowthData,
            'outfitStyleData' => $outfitStyleData,
            'categoryData' => $categoryData,
            'activityData' => $activityData
        ]);
    }
    
    #[Route('/users', name: 'app_admin_users')]
    public function users(): Response
    {
        $users = $this->userRepository->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }
    
    #[Route('/items', name: 'app_admin_items')]
    public function items(): Response
    {
        $items = $this->clothingItemRepository->findAll();
        
        return $this->render('admin/items.html.twig', [
            'items' => $items
        ]);
    }
    
    #[Route('/outfits', name: 'app_admin_outfits')]
    public function outfits(): Response
    {
        $outfits = $this->outfitRepository->findAll();
        
        return $this->render('admin/outfits.html.twig', [
            'outfits' => $outfits
        ]);
    }
    
    #[Route('/statistics', name: 'app_admin_statistics')]
    public function statistics(): Response
    {
        $userGrowthData = $this->getUserGrowthData();
        $outfitsByStyleData = $this->getOutfitStyleData();
        
        return $this->render('admin/statistics.html.twig', [
            'userGrowthData' => $userGrowthData,
            'outfitsByStyleData' => $outfitsByStyleData
        ]);
    }
    
    private function getUserGrowthData(): array
    {
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'];
        $totalUsers = count($this->userRepository->findAll());
        
        $data = [
            (int)($totalUsers * 0.3),  
            (int)($totalUsers * 0.45),
            (int)($totalUsers * 0.6),  
            (int)($totalUsers * 0.7),  
            (int)($totalUsers * 0.85), 
            $totalUsers                
        ];
        
        if ($totalUsers === 0) {
            $data = [12, 19, 25, 30, 35, 42];
        }

        return [
            'labels' => $months,
            'data' => $data
        ];
    }
    
    private function getOutfitStyleData(): array
    {
        $styleMap = [
            'casual' => 0,
            'formel' => 0,
            'sport' => 0,
            'soirée' => 0
        ];
        
        foreach ($this->outfitRepository->findAll() as $outfit) {
            $style = strtolower($outfit->getStyle() ?? '');
            if (isset($styleMap[$style])) {
                $styleMap[$style]++;
            }
        }
        
        foreach ($this->outfitHistoryRepository->findAll() as $history) {
            $style = strtolower($history->getStyle() ?? '');
            if (isset($styleMap[$style])) {
                $styleMap[$style]++;
            }
        }
        
        if (array_sum(array_values($styleMap)) === 0) {
            $styleMap = ['casual' => 45, 'formel' => 18, 'sport' => 32, 'soirée' => 12];
        }
        
        return [
            'labels' => array_map('ucfirst', array_keys($styleMap)),
            'data' => array_values($styleMap)
        ];
    }
    

    private function getCategoryData(): array
    {
        $categories = $this->categoryRepository->findAll();
        
        $categoryData = [];
        foreach ($categories as $category) {
            $categoryData[$category->getName() ?? 'Non catégorisé'] = count($category->getClothingItems());
        }
        
        if (empty($categoryData)) {
            $categoryData = [
                'T-shirts' => 45,
                'Jeans' => 38,
                'Robes' => 22,
                'Vestes' => 16,
                'Chaussures' => 30
            ];
        }
        
        return [
            'labels' => array_keys($categoryData),
            'data' => array_values($categoryData)
        ];
    }
    

    private function getActivityData(): array
    {
        $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        
        $outfitsGenerated = [8, 12, 15, 10, 20, 25, 18];
        $outfitsShared = [3, 5, 7, 4, 8, 10, 6];
        
        return [
            'labels' => $days,
            'outfitsGenerated' => $outfitsGenerated,
            'outfitsShared' => $outfitsShared
        ];
    }
}