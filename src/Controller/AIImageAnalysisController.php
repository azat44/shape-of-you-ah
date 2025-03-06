<?php
namespace App\Controller;

use App\Service\AIImageAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai/image')]
#[IsGranted('ROLE_USER')]
class AIImageAnalysisController extends AbstractController
{
    private $slugger;
    
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    #[Route('/analyze', name: 'app_ai_image_analyze')]
    public function showAnalyzeForm(): Response
    {
        return $this->render('ai/image_analyze.html.twig');
    }

    #[Route('/process', name: 'app_ai_image_process', methods: ['POST'])]
    public function processImage(Request $request, AIImageAnalysisService $aiService): Response
    {
        $imageFile = $request->files->get('image');
        
        if (!$imageFile) {
            $this->addFlash('error', 'Veuillez télécharger une image.');
            return $this->redirectToRoute('app_ai_image_analyze');
        }
        
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
        
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/temp/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $imageFile->move($uploadDir, $newFilename);
        $imagePath = '/uploads/temp/' . $newFilename;
        
        try {
            $detectedItems = $aiService->analyzeImage($uploadDir . $newFilename);
            
            return $this->render('ai/image_results.html.twig', [
                'imagePath' => $imagePath,
                'detectedItems' => $detectedItems
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'analyse de l\'image: ' . $e->getMessage());
            return $this->redirectToRoute('app_ai_image_analyze');
        }
    }
}