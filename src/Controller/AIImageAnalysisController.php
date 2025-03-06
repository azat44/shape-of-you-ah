<?php 
namespace App\Controller;  

use App\Service\AIRecommendationService; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 
use Symfony\Component\HttpFoundation\JsonResponse; 
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\Routing\Annotation\Route;  

class AIImageAnalysisController extends AbstractController {     
    private AIRecommendationService $aiService;      

    public function __construct(AIRecommendationService $aiService) {         
        $this->aiService = $aiService;     
    }      

    #[Route('/ai/image/analyze', name: 'app_ai_image_analyze', methods: ['GET'])]     
    public function showAnalyzePage() {         
        return $this->render('ai/image_analyze.html.twig');     
    }      

    #[Route('/ai/image/analyze-ajax', name: 'app_analyze_image', methods: ['POST'])]     
    public function analyzeImage(Request $request): JsonResponse {         
        $uploadedFile = $request->files->get('image');                  

        if (!$uploadedFile) {             
            return new JsonResponse([                 
                'status' => 'error',                  
                'message' => 'Aucune image envoyée'             
            ], 400);         
        }                  

        $imagePath = $uploadedFile->getRealPath();         
        $imageContent = file_get_contents($imagePath);                  

        try {             
            $result = $this->aiService->analyzeImage(null, null, $imageContent);             
            $detectedItems = $result['detected_items'] ?? [];                          

            $detectedText = "Éléments détectés :\n\n";             
            if (empty($detectedItems)) {                 
                $detectedText .= "Aucun élément de vêtement n'a été détecté dans cette image.";             
            } else {                 
                foreach ($detectedItems as $item) {                     
                    $detectedText .= "• {$item['name']}\n";                 
                }             
            }                          

            return new JsonResponse([                 
                'status' => 'success',                 
                'generated_text' => $detectedText,                 
                'detected_items' => $detectedItems             
            ]);         
        } catch (\Exception $e) {             
            return new JsonResponse([                 
                'status' => 'error',                 
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()             
            ], 500);         
        }     
    } 
}