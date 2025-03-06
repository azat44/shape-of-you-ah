<?php 
namespace App\Controller;  

use App\Service\AIRecommendationService; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 
use Symfony\Component\HttpFoundation\JsonResponse; 
use Symfony\Component\Routing\Annotation\Route; 
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\HttpFoundation\File\UploadedFile; 
use Symfony\Component\Security\Http\Attribute\IsGranted;  

#[Route('/ai')] 
#[IsGranted('ROLE_USER')] 
class AIImageUploadController extends AbstractController {     
    private AIRecommendationService $aiService;      

    public function __construct(AIRecommendationService $aiService)     {         
        $this->aiService = $aiService;     
    }      

    #[Route('/image/upload', name: 'app_upload_image', methods: ['POST'])]     
    public function uploadImage(Request $request): JsonResponse     {         
        $imageFile = $request->files->get('image');         
        if (!$imageFile instanceof UploadedFile) {             
            return $this->json([                 
                'status' => 'error',                  
                'message' => 'Aucune image envoyée'             
            ], Response::HTTP_BAD_REQUEST);         
        }          

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/temp/';         
        if (!file_exists($uploadDir)) {             
            mkdir($uploadDir, 0777, true);         
        }                  

        $extension = $imageFile->guessExtension() ?: 'jpg';         
        $newFilename = uniqid('tenue_', true) . '.' . $extension;         
        $imageFile->move($uploadDir, $newFilename);                  

        try {             
            $result = $this->aiService->analyzeImage($uploadDir, $newFilename);             
            $detectedItems = $result['detected_items'] ?? [];                          

            $detectedText = "Éléments détectés dans votre tenue :\n\n";             
            if (empty($detectedItems)) {                 
                $detectedText .= "Aucun élément n'a été détecté dans cette image.";             
            } else {                 
                foreach ($detectedItems as $item) {                     
                    $detectedText .= "• {$item['name']}\n";                 
                }             
            }                          

            return $this->json([                 
                'status' => 'success',                 
                'image_path' => '/uploads/temp/' . $newFilename,                 
                'generated_text' => $detectedText,                 
                'detected_items' => $detectedItems             
            ], Response::HTTP_OK);         
        } catch (\Exception $e) {             
            if (file_exists($uploadDir . $newFilename)) {                 
                unlink($uploadDir . $newFilename);             
            }                          

            return $this->json([                 
                'status' => 'error',                 
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()             
            ], Response::HTTP_INTERNAL_SERVER_ERROR);         
        }     
    } 
}