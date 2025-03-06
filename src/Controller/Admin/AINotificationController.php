<?php

namespace App\Controller\Admin;

use App\Service\AIAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notifications')]
#[IsGranted('ROLE_ADMIN')]
class AINotificationController extends AbstractController
{
    private AIAlertService $alertService;
    
    public function __construct(AIAlertService $alertService)
    {
        $this->alertService = $alertService;
    }
    
    #[Route('/', name: 'app_admin_notifications')]
    public function index(): Response
    {
        $notifications = $this->alertService->getActiveNotifications();
        
        return $this->render('admin/notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }
    
    #[Route('/{id}/resolve', name: 'app_admin_notification_resolve', methods: ['POST'])]
    public function resolve(int $id): JsonResponse
    {
        $success = $this->alertService->resolveNotification($id);
        
        if ($success) {
            return $this->json([
                'status' => 'success',
                'message' => 'Notification marquée comme résolue'
            ]);
        }
        
        return $this->json([
            'status' => 'error',
            'message' => 'Notification non trouvée'
        ], 404);
    }
}