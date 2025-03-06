<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\ClothingItemRepository;
use App\Repository\OutfitRepository;
use App\Repository\OutfitHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        ClothingItemRepository $clothingItemRepository,
        OutfitRepository $outfitRepository,
        OutfitHistoryRepository $outfitHistoryRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->clothingItemRepository = $clothingItemRepository;
        $this->outfitRepository = $outfitRepository;
        $this->outfitHistoryRepository = $outfitHistoryRepository;
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        $totalUsers = count($this->userRepository->findAll());
        $totalItems = count($this->clothingItemRepository->findAll());
        $totalOutfits = count($this->outfitRepository->findAll());
        $totalSharedOutfits = count($this->outfitHistoryRepository->findSharedOutfits());
        
        $notifications = [
            [
                'id' => 1,
                'message' => 'L\'IA a détecté un nouvel élément : "Veste en jean" qui n\'est pas présent dans la base',
                'date' => new \DateTime('-2 hours'),
                'status' => 'new',
                'type' => 'detection'
            ],
            [
                'id' => 2,
                'message' => 'Des doublons potentiels ont été détectés : "T-shirt blanc" et "T-shirt en coton blanc"',
                'date' => new \DateTime('-1 day'),
                'status' => 'pending',
                'type' => 'duplicate'
            ],
            [
                'id' => 3,
                'message' => 'Nouveau partenaire suggéré : "EcoFashion" spécialisé dans les vêtements éco-responsables',
                'date' => new \DateTime('-3 days'),
                'status' => 'resolved',
                'type' => 'suggestion'
            ]
        ];
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users' => $totalUsers,
                'items' => $totalItems,
                'outfits' => $totalOutfits,
                'shared_outfits' => $totalSharedOutfits
            ],
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