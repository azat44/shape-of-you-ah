<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Profile;
use App\Form\UserType;
use App\Form\CategoryType;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\ClothingItemRepository;
use App\Repository\OutfitRepository;
use App\Repository\OutfitHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    private UserPasswordHasherInterface $passwordHasher;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        ClothingItemRepository $clothingItemRepository,
        OutfitRepository $outfitRepository,
        OutfitHistoryRepository $outfitHistoryRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->clothingItemRepository = $clothingItemRepository;
        $this->outfitRepository = $outfitRepository;
        $this->outfitHistoryRepository = $outfitHistoryRepository;
        $this->passwordHasher = $passwordHasher;
    }


    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        $totalUsers = count($this->userRepository->findAll());
        $totalItems = count($this->clothingItemRepository->findAll());
        $totalOutfits = count($this->outfitRepository->findAll());
        $totalSharedOutfits = count($this->outfitHistoryRepository->findSharedOutfits());
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users' => $totalUsers,
                'items' => $totalItems,
                'outfits' => $totalOutfits,
                'shared_outfits' => $totalSharedOutfits
            ]
        ]);
    }
    
 
    #[Route('/charts/user-growth', name: 'app_admin_charts_user_growth')]
    public function userGrowthChart(): Response
    {
        return $this->render('admin/charts/user_growth.html.twig');
    }
    

    #[Route('/charts/user-engagement', name: 'app_admin_charts_user_engagement')]
    public function userEngagementChart(): Response
    {
        return $this->render('admin/charts/user_engagement.html.twig');
    }
    

    #[Route('/table', name: 'app_admin_table')]
    public function table(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('admin/components/table.html.twig', [
            'users' => $users,
        ]);
    }
    

    #[Route('/form', name: 'app_admin_form')]
    public function form(): Response
    {
        return $this->render('admin/components/form.html.twig');
    }


    #[Route('/categories', name: 'app_admin_category_index', methods: ['GET'])]
    public function categoryIndex(): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $this->categoryRepository->findAll(),
        ]);
    }

    #[Route('/categories/new', name: 'app_admin_category_new', methods: ['GET', 'POST'])]
    public function newCategory(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }


    #[Route('/categories/{id}', name: 'app_admin_category_show', methods: ['GET'])]
    public function showCategory(Category $category): Response
    {
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }


    #[Route('/categories/{id}/edit', name: 'app_admin_category_edit', methods: ['GET', 'POST'])]
    public function editCategory(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }


    #[Route('/categories/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER);
    }

    

    #[Route('/users', name: 'app_admin_user_index', methods: ['GET'])]
    public function userIndex(): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $this->userRepository->findAll(),
        ]);
    }


    #[Route('/users/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function newUser(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $user->getPassword()
                )
            );
            
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/users/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function showUser(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getPassword()) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword(
                        $user,
                        $user->getPassword()
                    )
                );
            }
            
            $user->setUpdatedAt(new \DateTime());
            
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }


    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
    

    #[Route('/notifications', name: 'app_admin_notifications')]
    public function aiNotifications(): Response
    {
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
        
        return $this->render('admin/notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }
    

    #[Route('/stats/trends', name: 'app_admin_stats_trends')]
    public function fashionTrendsStats(): Response
    {
        $trendsData = [
            'popular_styles' => [
                ['name' => 'Casual', 'count' => 45],
                ['name' => 'Sport', 'count' => 32],
                ['name' => 'Formel', 'count' => 18],
                ['name' => 'Soirée', 'count' => 12]
            ],
            'popular_categories' => [
                ['name' => 'Haut', 'count' => 58],
                ['name' => 'Bas', 'count' => 42],
                ['name' => 'Chaussures', 'count' => 35],
                ['name' => 'Accessoires', 'count' => 20]
            ],
            'popular_colors' => [
                ['name' => 'Noir', 'count' => 40],
                ['name' => 'Blanc', 'count' => 35],
                ['name' => 'Bleu', 'count' => 30],
                ['name' => 'Gris', 'count' => 25]
            ]
        ];
        
        return $this->render('admin/stats/trends.html.twig', [
            'trendsData' => $trendsData
        ]);
    }
}