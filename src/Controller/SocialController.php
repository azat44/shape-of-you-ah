<?php

namespace App\Controller;

use App\Repository\OutfitHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/social')]
#[IsGranted('ROLE_USER')]
class SocialController extends AbstractController
{
    private OutfitHistoryRepository $outfitHistoryRepository;
    
    public function __construct(OutfitHistoryRepository $outfitHistoryRepository)
    {
        $this->outfitHistoryRepository = $outfitHistoryRepository;
    }

    #[Route('/', name: 'app_social_feed')]
    public function feed(): Response
    {
        $sharedOutfits = $this->outfitHistoryRepository->findSharedOutfits();
        
        $outfits = [];
        foreach ($sharedOutfits as $outfit) {
            $outfits[] = [
                'id' => $outfit->getId(),
                'user' => [
                    'id' => $outfit->getUser()->getId(),
                    'name' => $outfit->getUser()->getNom(),
                ],
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription(),
                'items' => $outfit->getOutfitItems(),
                'image_url' => $outfit->getImageUrl(),
                'style' => $outfit->getStyle(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'likes' => random_int(5, 50),
            ];
        }
        
        if (empty($outfits)) {
            $outfits = $this->getDemoOutfits();
        }
        
        return $this->render('social/feed.html.twig', ['outfits' => $outfits]);
    }

 
    #[Route('/outfit/{id}', name: 'app_social_outfit_detail')]
    public function outfitDetail(int $id): Response
    {
        $outfit = $this->outfitHistoryRepository->find($id);
        
        if (!$outfit) {
            $outfitData = $this->getDemoOutfitById($id);
        } else {
            $outfitData = [
                'id' => $outfit->getId(),
                'user' => [
                    'id' => $outfit->getUser()->getId(),
                    'name' => $outfit->getUser()->getNom(),
                ],
                'title' => $outfit->getTitle() ?: 'Tenue sans titre',
                'description' => $outfit->getDescription(),
                'items' => $outfit->getOutfitItems(),
                'image_url' => $outfit->getImageUrl(),
                'style' => $outfit->getStyle(),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d H:i:s'),
                'likes' => random_int(5, 50),
            ];
        }
        
        return $this->render('social/detail.html.twig', ['outfit' => $outfitData]);
    }


    private function getDemoOutfits(): array
    {
        return [
            [
                'id' => 1001,
                'user' => ['id' => 101, 'name' => 'Sophie Martin'],
                'title' => 'Look casual été Nike',
                'description' => 'Parfait pour une journée décontractée en ville.',
                'items' => [['name' => 'T-shirt Nike'], ['name' => 'Short Nike']],
                'image_url' => '/images/tshirt-technique.jpg',
                'style' => 'casual',
                'created_at' => '2023-07-15 10:30:00',
                'likes' => 24,
            ],
            [
                'id' => 1002,
                'user' => ['id' => 102, 'name' => 'Thomas Dubois'],
                'title' => 'Tenue sport Adidas',
                'description' => 'Idéal pour la salle de sport.',
                'items' => [['name' => 'T-shirt Adidas'], ['name' => 'Short de sport']],
                'image_url' => '/images/short-sport.webp',
                'style' => 'sport',
                'created_at' => '2023-07-20 14:45:00',
                'likes' => 37,
            ],
        ];
    }


    private function getDemoOutfitById(int $id): array
    {
        $demoOutfits = [
            1001 => [
                'id' => 1001,
                'user' => ['id' => 101, 'name' => 'Sophie Martin'],
                'title' => 'Look casual été Nike',
                'description' => 'Parfait pour une journée décontractée en ville.',
                'items' => [['name' => 'T-shirt Nike'], ['name' => 'Short Nike']],
                'image_url' => '/images/tshirt-technique.jpg',
                'style' => 'casual',
                'created_at' => '2023-07-15 10:30:00',
                'likes' => 24,
            ],
            1002 => [
                'id' => 1002,
                'user' => ['id' => 102, 'name' => 'Thomas Dubois'],
                'title' => 'Tenue sport Adidas',
                'description' => 'Idéal pour la salle de sport.',
                'items' => [['name' => 'T-shirt Adidas'], ['name' => 'Short de sport']],
                'image_url' => '/images/short-sport.webp',
                'style' => 'sport',
                'created_at' => '2023-07-20 14:45:00',
                'likes' => 37,
            ],
        ];
        
        return $demoOutfits[$id] ?? $demoOutfits[1001];
    }
}