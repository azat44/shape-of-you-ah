<?php
namespace App\Controller;

use App\Service\AIRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/outfits/generator')]
#[IsGranted('ROLE_USER')]
class AIOutfitGeneratorController extends AbstractController
{
    private $aiRecommendationService;
    
    public function __construct(AIRecommendationService $aiRecommendationService)
    {
        $this->aiRecommendationService = $aiRecommendationService;
    }
    
    #[Route('/', name: 'app_outfit_generator')]
    public function showGenerator(): Response
    {
        return $this->render('outfit/generator.html.twig');
    }
    
    #[Route('/generate', name: 'app_outfit_generate', methods: ['POST'])]
    public function generateOutfit(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        $data = [];
        if ($request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $occasion = $data['occasion'] ?? $request->request->get('occasion');
        $season = $data['season'] ?? $request->request->get('season');
        
        try {
            $wardrobeItems = [];
            foreach ($user->getWardrobeItems() as $wardrobeItem) {
                $item = $wardrobeItem->getClothingItem();
                $wardrobeItems[] = [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'category' => $item->getCategory()->getName(),
                    'color' => $item->getColor() ?? '',
                    'style' => $item->getStyle() ?? '',
                    'image_url' => $item->getImageUrl() ?? '/images/placeholder.jpg'
                ];
            }
            
            if (empty($wardrobeItems)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Vous devez ajouter des vêtements à votre garde-robe pour générer des tenues.'
                ]);
            }
            
            $recommendations = $this->aiRecommendationService->getRecommendationsForUser(
                $user,
                $wardrobeItems,
                $occasion,
                $season
            );
            
            return $this->json([
                'success' => true,
                'recommendations' => $recommendations,
                'filters' => [
                    'occasion' => $occasion,
                    'season' => $season
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
}