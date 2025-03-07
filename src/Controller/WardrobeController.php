<?php

namespace App\Controller;

use App\Entity\ClothingItem;
use App\Entity\Category;
use App\Entity\UserWardrobe;
use App\Form\ClothingItemType;
use App\Repository\ClothingItemRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserWardrobeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/wardrobe')]
#[IsGranted('ROLE_USER')]
class WardrobeController extends AbstractController
{
    private $entityManager;
    private $clothingItemRepository;
    private $categoryRepository;
    private $filesystem;
    private $slugger;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        ClothingItemRepository $clothingItemRepository,
        CategoryRepository $categoryRepository,
        Filesystem $filesystem,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->clothingItemRepository = $clothingItemRepository;
        $this->categoryRepository = $categoryRepository;
        $this->filesystem = $filesystem;
        $this->slugger = $slugger;
    }


    #[Route('/', name: 'app_wardrobe')]
    public function index(Request $request): Response
    {
        $predefinedCategories = [
            'T-shirts',
            'Pantalons', 
            'Robes', 
            'Vestes', 
            'Chaussures', 
            'Accessoires', 
            'Pulls', 
            'Shorts', 
            'Jupes', 
            'Chemises'
        ];
    
        $user = $this->getUser();
        $query = $request->query->get('q');
        $wardrobeItems = $user->getWardrobeItems();
        
        if ($query) {
            $wardrobeItems = $wardrobeItems->filter(function($item) use ($query) {
                $clothingItem = $item->getClothingItem();
                $name = strtolower($clothingItem->getName());
                $description = strtolower($clothingItem->getDescription() ?? '');
                $searchTerm = strtolower($query);
                
                return strpos($name, $searchTerm) !== false || 
                       strpos($description, $searchTerm) !== false;
            });
        }
        
        $wardrobeByCategory = [];
        foreach ($wardrobeItems as $item) {
            $category = $item->getClothingItem()->getCategory()->getName();
            $wardrobeByCategory[$category][] = $item;
        }
        
        $allClothingItems = $this->clothingItemRepository->findAll();
        
        return $this->render('wardrobe/index.html.twig', [
            'wardrobeItems' => $wardrobeItems,
            'wardrobeByCategory' => $wardrobeByCategory,
            'allClothingItems' => $allClothingItems,
            'searchQuery' => $query,
            'predefinedCategories' => $predefinedCategories
        ]);
    }
    

    #[Route('/browse', name: 'app_wardrobe_browse')]
    public function browse(Request $request): Response
    {
        $user = $this->getUser();
        $query = $request->query->get('q');
        $categoryId = $request->query->get('category');
        $color = $request->query->get('color');
        $style = $request->query->get('style');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        
        $filters = array_filter([
            'category' => $categoryId,
            'color' => $color,
            'style' => $style,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice
        ]);
        
        $allClothingItems = $this->clothingItemRepository->searchItems($query, $filters);
        $categories = $this->categoryRepository->findAll();
        
        $clothingItems = array_map(function($item) use ($user) {
            return [
                'item' => $item,
                'inWardrobe' => $user->hasItemInWardrobe($item)
            ];
        }, $allClothingItems);
        
        return $this->render('wardrobe/browse.html.twig', [
            'clothingItems' => $clothingItems,
            'categories' => $categories,
            'searchQuery' => $query,
            'filters' => [
                'category' => $categoryId,
                'color' => $color,
                'style' => $style,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice
            ]
        ]);
    }
    

    #[Route('/add/{id}', name: 'app_wardrobe_add_item', methods: ['POST'])]
    public function addItem(ClothingItem $clothingItem): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user->hasItemInWardrobe($clothingItem)) {
            return $this->json(['error' => 'Ce vêtement est déjà dans votre garde-robe'], 400);
        }
        
        $wardrobeItem = new UserWardrobe();
        $wardrobeItem->setUser($user);
        $wardrobeItem->setClothingItem($clothingItem);
        $wardrobeItem->setIsFavorite(false);
        $wardrobeItem->setUsageCount(0);
        
        $this->entityManager->persist($wardrobeItem);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Article ajouté à votre garde-robe'
        ]);
    }
    

    #[Route('/remove/{id}', name: 'app_wardrobe_remove_item', methods: ['POST'])]
    public function removeItem(ClothingItem $clothingItem): JsonResponse
    {
        $user = $this->getUser();
        $wardrobeItem = $user->getWardrobeItem($clothingItem);
        
        if (!$wardrobeItem) {
            return $this->json(['error' => 'Ce vêtement n\'est pas dans votre garde-robe'], 400);
        }
        
        $this->entityManager->remove($wardrobeItem);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Article retiré de votre garde-robe'
        ]);
    }
    

    #[Route('/toggle-favorite/{id}', name: 'app_wardrobe_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(ClothingItem $clothingItem): JsonResponse
    {
        $user = $this->getUser();
        $wardrobeItem = $user->getWardrobeItem($clothingItem);
        
        if (!$wardrobeItem) {
            return $this->json(['error' => 'Ce vêtement n\'est pas dans votre garde-robe'], 400);
        }
        
        $wardrobeItem->setIsFavorite(!$wardrobeItem->isIsFavorite());
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Préférence mise à jour',
            'isFavorite' => $wardrobeItem->isIsFavorite()
        ]);
    }
    

    #[Route('/add-custom-item', name: 'app_wardrobe_add_custom_item', methods: ['POST'])]
    public function addCustomItem(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        $name = $request->request->get('name');
        $categoryName = $request->request->get('category');
        $imageFile = $request->files->get('imageFile');
        
        if (!$name || !$categoryName || !$imageFile) {
            return $this->json(['error' => 'Informations manquantes'], 400);
        }
        
        $category = $this->entityManager->getRepository(Category::class)
            ->findOneBy(['name' => $categoryName]);
        
        if (!$category) {
            $category = new Category();
            $category->setName($categoryName);
            $this->entityManager->persist($category);
        }
        
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
        
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/clothing/';
        
        if (!$this->filesystem->exists($uploadDir)) {
            $this->filesystem->mkdir($uploadDir);
        }
        
        $imageFile->move($uploadDir, $newFilename);
        
        $clothingItem = new ClothingItem();
        $clothingItem->setName($name);
        $clothingItem->setDescription($request->request->get('description'));
        $clothingItem->setCategory($category);
        $clothingItem->setImageUrl('/uploads/clothing/' . $newFilename);
        $clothingItem->setColor($request->request->get('color'));
        $clothingItem->setStyle($request->request->get('style'));
        
        $price = $request->request->get('price');
        if ($price) {
            $clothingItem->setPrice(floatval($price));
        }
        
        $this->entityManager->persist($clothingItem);
        
        $wardrobeItem = new UserWardrobe();
        $wardrobeItem->setUser($user);
        $wardrobeItem->setClothingItem($clothingItem);
        $wardrobeItem->setIsFavorite(false);
        $wardrobeItem->setUsageCount(0);
        
        $this->entityManager->persist($wardrobeItem);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Vêtement ajouté avec succès',
            'item' => [
                'id' => $clothingItem->getId(),
                'name' => $clothingItem->getName(),
                'category' => $category->getName(),
                'imageUrl' => $clothingItem->getImageUrl()
            ]
        ]);
    }


    #[Route('/items', name: 'app_clothing_item_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function listClothingItems(): Response
    {
        return $this->render('clothing_item/index.html.twig', [
            'clothing_items' => $this->clothingItemRepository->findAll(),
        ]);
    }

    #[Route('/items/new', name: 'app_clothing_item_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newClothingItem(Request $request): Response
    {
        $clothingItem = new ClothingItem();
        $form = $this->createForm(ClothingItemType::class, $clothingItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($clothingItem);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_clothing_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('clothing_item/new.html.twig', [
            'clothing_item' => $clothingItem,
            'form' => $form,
        ]);
    }

    #[Route('/items/{id}', name: 'app_clothing_item_show', methods: ['GET'])]
    public function showClothingItem(ClothingItem $clothingItem): Response
    {
        return $this->render('clothing_item/show.html.twig', [
            'clothing_item' => $clothingItem,
        ]);
    }

    #[Route('/items/{id}/edit', name: 'app_clothing_item_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editClothingItem(Request $request, ClothingItem $clothingItem): Response
    {
        $form = $this->createForm(ClothingItemType::class, $clothingItem);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
    
            return $this->redirectToRoute('app_clothing_item_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('clothing_item/edit.html.twig', [
            'clothing_item' => $clothingItem,
            'form' => $form,
        ]);
    }


    #[Route('/items/{id}/delete', name: 'app_clothing_item_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteClothingItem(Request $request, ClothingItem $clothingItem): Response
    {
        if ($this->isCsrfTokenValid('delete'.$clothingItem->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($clothingItem);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_clothing_item_index', [], Response::HTTP_SEE_OTHER);
    }
}