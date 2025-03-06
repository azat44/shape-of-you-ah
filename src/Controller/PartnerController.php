<?php

namespace App\Controller;

use App\Repository\ClothingItemRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la gestion des partenaires et redirection vers les sites d'achat
 */
#[Route('/partner')]
#[IsGranted('ROLE_USER')]
class PartnerController extends AbstractController
{
    private ClothingItemRepository $clothingItemRepository;
    private CategoryRepository $categoryRepository;
    
    public function __construct(
        ClothingItemRepository $clothingItemRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->clothingItemRepository = $clothingItemRepository;
        $this->categoryRepository = $categoryRepository;
    }
    

    #[Route('/shop', name: 'app_partner_shop')]
    public function shop(): Response
    {
        $partners = [
            [
                'name' => 'Nike',
                'url' => 'https://www.nike.com',
                'categories' => ['Sport', 'Streetwear', 'Chaussures']
            ],
            [
                'name' => 'Adidas',
                'url' => 'https://www.adidas.fr',
                'categories' => ['Sport', 'Chaussures', 'Vêtements']
            ],
            [
                'name' => 'Zara',
                'url' => 'https://www.zara.com',
                'categories' => ['Tendances', 'Fast Fashion', 'Accessoires']
            ],
            [
                'name' => 'Vinted',
                'url' => 'https://www.vinted.fr',
                'categories' => ['Seconde main', 'Vintage', 'Outlet']
            ]
        ];

        $clothingItems = $this->clothingItemRepository->findAll();
        $categories = $this->categoryRepository->findAll();

        return $this->render('partner/shop.html.twig', [
            'partners' => $partners,
            'clothingItems' => $clothingItems,
            'categories' => $categories
        ]);
    }


    #[Route('/buy/{id}', name: 'app_partner_buy')]
    public function buyItem(int $id): Response
    {
        $item = $this->clothingItemRepository->find($id);
        
        if ($item && $item->getPartnerLink()) {
            return $this->redirect($item->getPartnerLink());
        }
        
        $partnerLinks = [
            'Haut' => 'https://www.zara.com/fr/fr/homme-t-shirts-l855.html',
            'Bas' => 'https://www.levi.com/FR/fr_FR/vetements/homme/jeans/c/levi_clothing_men_jeans',
            'Chaussures' => 'https://www.nike.com/fr/w/chaussures-y7ok',
            'Accessoires' => 'https://www2.hm.com/fr_fr/homme/acheter-par-produit/accessoires.html'
        ];
        
        $category = $item ? $item->getCategory()->getName() : '';
        $partnerUrl = $partnerLinks[$category] ?? 'https://www.adidas.fr/vetements';
        
        return $this->redirect($partnerUrl);
    }
    

    #[Route('/links', name: 'app_partner_links', methods: ['GET'])]
    public function getPartnerLinks(): Response
    {
        $partnerLinks = [
            [
                'name' => 'Mode Durable',
                'url' => 'https://modedurable.fr',
                'categories' => ['éco-responsable', 'upcycling']
            ],
            [
                'name' => 'Boutique Tendance',
                'url' => 'https://boutiquetendance.com',
                'categories' => ['casual', 'chic', 'sportswear']
            ],
            [
                'name' => 'SecondHand Fashion',
                'url' => 'https://secondhandfashion.fr',
                'categories' => ['seconde main', 'vintage', 'occasion']
            ],
            [
                'name' => 'Eco-Style',
                'url' => 'https://eco-style.com',
                'categories' => ['éco-conception', 'matières recyclées']
            ]
        ];
        
        return $this->render('partner/links.html.twig', [
            'partners' => $partnerLinks
        ]);
    }
    

    #[Route('/search', name: 'app_partner_search')]
    public function searchPartnerProducts(Request $request): Response
    {
        $query = $request->query->get('q');
        $category = $request->query->get('category');
        
        $mockProducts = [
            [
                'name' => 'T-shirt coton bio',
                'brand' => 'Mode Durable',
                'price' => 29.99,
                'imageUrl' => '/images/tshirt-blanc.jpg',
                'partnerUrl' => 'https://modedurable.fr/products/tshirt-coton-bio'
            ],
            [
                'name' => 'Jean slim recyclé',
                'brand' => 'Eco-Style',
                'price' => 59.99,
                'imageUrl' => '/images/jean-bleu.webp',
                'partnerUrl' => 'https://eco-style.com/products/jean-slim-recycle'
            ],
            [
                'name' => 'Veste vintage',
                'brand' => 'SecondHand Fashion',
                'price' => 45.00,
                'imageUrl' => '/images/veste-velour.webp',
                'partnerUrl' => 'https://secondhandfashion.fr/products/veste-vintage'
            ]
        ];
        
        if ($query || $category) {
            $mockProducts = array_filter($mockProducts, function($product) use ($query, $category) {
                $matchQuery = !$query || stripos($product['name'], $query) !== false;
                $matchCategory = !$category || strtolower($product['brand']) === strtolower($category);
                return $matchQuery && $matchCategory;
            });
        }
        
        return $this->render('partner/search_results.html.twig', [
            'products' => $mockProducts,
            'query' => $query,
            'category' => $category
        ]);
    }
}