<?php
namespace App\Controller\Admin;

use App\Service\AdminAIService;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\ClothingItemRepository;
use App\Repository\OutfitRepository;
use App\Repository\OutfitHistoryRepository;
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
    
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        ClothingItemRepository $clothingItemRepository,
        OutfitRepository $outfitRepository,
        OutfitHistoryRepository $outfitHistoryRepository,
        AdminAIService $adminAIService
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->clothingItemRepository = $clothingItemRepository;
        $this->outfitRepository = $outfitRepository;
        $this->outfitHistoryRepository = $outfitHistoryRepository;
        $this->adminAIService = $adminAIService;
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
    
        $notifications = [
        ];
    
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users' => $totalUsers,
                'items' => $totalItems,
                'outfits' => $totalOutfits,
                'shared_outfits' => $totalSharedOutfits
            ],
            'aiInsights' => $aiInsights['insights'] ?? 'Aucun insight disponible',
            'notifications' => $notifications
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
        $userGrowthData = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
            'data' => [12, 19, 25, 30, 35, 42]
        ];
        
        $outfitsByStyleData = [
            'labels' => ['Casual', 'Formel', 'Sport', 'Soirée'],
            'data' => [45, 18, 32, 12]
        ];
        
        return $this->render('admin/statistics.html.twig', [
            'userGrowthData' => $userGrowthData,
            'outfitsByStyleData' => $outfitsByStyleData
        ]);
    }
}