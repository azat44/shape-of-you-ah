<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\ClothingItem;
use App\Entity\Partner;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\ClothingItemRepository;
use App\Repository\PartnerRepository;
use App\Repository\AINotificationRepository;
use App\Repository\OutfitRepository;
use App\Repository\OutfitHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        ClothingItemRepository $clothingItemRepository,
        CategoryRepository $categoryRepository,
        AINotificationRepository $aiNotificationRepository,
        OutfitRepository $outfitRepository = null,
        OutfitHistoryRepository $outfitHistoryRepository = null
    ): Response
    {
        $stats = [
            'users' => count($userRepository->findAll()),
            'items' => count($clothingItemRepository->findAll()),
            'categories' => count($categoryRepository->findAll()),
            'notifications' => count($aiNotificationRepository->findActiveNotifications()),
            'outfits' => $outfitRepository ? count($outfitRepository->findAll()) : 0,
            'shared_outfits' => $outfitHistoryRepository ? count($outfitHistoryRepository->findSharedOutfits()) : 0
        ];
        
        $recentUsers = $userRepository->findRecentUsers(5);
        $recentItems = $clothingItemRepository->findLatestItems(5);
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentItems' => $recentItems
        ]);
    }
    
    // ------------------- USERS CRUD -------------------
    
    #[Route('/users', name: 'app_admin_users')]
    public function usersList(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }
    
    #[Route('/users/{id}', name: 'app_admin_users_show')]
    public function showUser(User $user): Response
    {
        return $this->render('admin/user_details.html.twig', [
            'user' => $user
        ]);
    }
    
    #[Route('/users/{id}/edit', name: 'app_admin_users_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, User $user): Response
    {
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setNom($request->request->get('nom'));
            $user->setRoles($request->request->get('roles', []));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur mis à jour avec succès');
            return $this->redirectToRoute('app_admin_users');
        }
        
        return $this->render('admin/user_edit.html.twig', [
            'user' => $user
        ]);
    }
    
    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        }
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    // ------------------- CATEGORIES CRUD -------------------
    
    #[Route('/categories', name: 'app_admin_categories')]
    public function categoriesList(CategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/categories.html.twig', [
            'categories' => $categoryRepository->findAll()
        ]);
    }
    
    #[Route('/categories/new', name: 'app_admin_categories_new', methods: ['GET', 'POST'])]
    public function newCategory(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $category = new Category();
            $category->setName($request->request->get('name'));
            $category->setDescription($request->request->get('description'));
            
            $this->entityManager->persist($category);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Catégorie créée avec succès');
            return $this->redirectToRoute('app_admin_categories');
        }
        
        return $this->render('admin/category_new.html.twig');
    }
    
    #[Route('/categories/{id}/edit', name: 'app_admin_categories_edit', methods: ['GET', 'POST'])]
    public function editCategory(Request $request, Category $category): Response
    {
        if ($request->isMethod('POST')) {
            $category->setName($request->request->get('name'));
            $category->setDescription($request->request->get('description'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Catégorie mise à jour avec succès');
            return $this->redirectToRoute('app_admin_categories');
        }
        
        return $this->render('admin/category_edit.html.twig', [
            'category' => $category
        ]);
    }
    
    #[Route('/categories/{id}/delete', name: 'app_admin_categories_delete', methods: ['GET', 'POST'])]
    public function deleteCategory(Category $category): Response
    {
        try {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_admin_categories');
    }
    
    // ------------------- CLOTHING ITEMS CRUD -------------------
    
    #[Route('/items', name: 'app_admin_items')]
    public function itemsList(ClothingItemRepository $clothingItemRepository): Response
    {
        return $this->render('admin/items.html.twig', [
            'items' => $clothingItemRepository->findAll()
        ]);
    }
    
    #[Route('/items/{id}/edit', name: 'app_admin_items_edit', methods: ['GET', 'POST'])]
    public function editItem(Request $request, ClothingItem $item, CategoryRepository $categoryRepository): Response
    {
        if ($request->isMethod('POST')) {
            $category = $categoryRepository->find($request->request->get('category'));
            
            $item->setName($request->request->get('name'));
            $item->setDescription($request->request->get('description'));
            $item->setCategory($category);
            $item->setColor($request->request->get('color'));
            $item->setStyle($request->request->get('style'));
            $item->setPrice($request->request->get('price') ? floatval($request->request->get('price')) : null);
            $item->setPartnerLink($request->request->get('partnerLink'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Vêtement mis à jour avec succès');
            return $this->redirectToRoute('app_admin_items');
        }
        
        return $this->render('admin/item_edit.html.twig', [
            'item' => $item,
            'categories' => $categoryRepository->findAll()
        ]);
    }
    
    #[Route('/items/{id}/delete', name: 'app_admin_items_delete', methods: ['POST'])]
    public function deleteItem(Request $request, ClothingItem $item): Response
    {
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            try {
                $em = $this->entityManager;
                $query = $em->createQuery('DELETE FROM App\Entity\UserWardrobe uw WHERE uw.clothingItem = :item');
                $query->setParameter('item', $item);
                $query->execute();
                
                $em->remove($item);
                $em->flush();
                
                $this->addFlash('success', 'Vêtement supprimé avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
            }
        }
        
        return $this->redirectToRoute('app_admin_items');
    }
    
    // ------------------- PARTNER LINKS CRUD -------------------
    
    #[Route('/partners', name: 'app_admin_partners')]
    public function partnersList(PartnerRepository $partnerRepository): Response
    {
        return $this->render('admin/partners.html.twig', [
            'partners' => $partnerRepository->findAll()
        ]);
    }
    
    #[Route('/partners/new', name: 'app_admin_partners_new', methods: ['GET', 'POST'])]
    public function newPartner(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $partner = new Partner();
            $partner->setName($request->request->get('name'));
            $partner->setUrl($request->request->get('url'));
            $partner->setDescription($request->request->get('description'));
            
            $this->entityManager->persist($partner);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Partenaire ajouté avec succès');
            return $this->redirectToRoute('app_admin_partners');
        }
        
        return $this->render('admin/partner_new.html.twig');
    }
    
    #[Route('/partners/{id}/edit', name: 'app_admin_partners_edit', methods: ['GET', 'POST'])]
    public function editPartner(Request $request, Partner $partner): Response
    {
        if ($request->isMethod('POST')) {
            $partner->setName($request->request->get('name'));
            $partner->setUrl($request->request->get('url'));
            $partner->setDescription($request->request->get('description'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Partenaire mis à jour avec succès');
            return $this->redirectToRoute('app_admin_partners');
        }
        
        return $this->render('admin/partner_edit.html.twig', [
            'partner' => $partner
        ]);
    }
    
    #[Route('/partners/{id}/delete', name: 'app_admin_partners_delete', methods: ['POST'])]
    public function deletePartner(Request $request, Partner $partner): Response
    {
        if ($this->isCsrfTokenValid('delete'.$partner->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($partner);
            $this->entityManager->flush();
            $this->addFlash('success', 'Partenaire supprimé avec succès');
        }
        
        return $this->redirectToRoute('app_admin_partners');
    }
    
    // ------------------- AI ALERTS -------------------
    
    #[Route('/alerts', name: 'app_admin_alerts')]
    public function aiAlerts(AINotificationRepository $aiNotificationRepository): Response
    {
        return $this->render('admin/alerts.html.twig', [
            'notifications' => $aiNotificationRepository->findActiveNotifications()
        ]);
    }
    
    #[Route('/alerts/{id}/resolve', name: 'app_admin_alerts_resolve', methods: ['POST'])]
    public function resolveAlert(Request $request, $id, AINotificationRepository $aiNotificationRepository): JsonResponse
    {
        $notification = $aiNotificationRepository->find($id);
        
        if (!$notification) {
            return $this->json(['success' => false, 'message' => 'Notification non trouvée'], 404);
        }
        
        $notification->setStatus('resolved');
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }

    #[Route('/items/{id}/view', name: 'app_admin_items_show')]
public function showItem(ClothingItem $item): Response
{
    return $this->render('admin/item_details.html.twig', [
        'item' => $item
    ]);
}
}