<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class MainController extends AbstractController
{

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('home/index.html.twig');
    }

    #[Route('/main', name: 'app_main')]
    public function main(): Response
    {
        return $this->render('main/index.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('about/index.html.twig');
    }

    #[Route('/theme/{theme}', name: 'app_theme_toggle')]
    public function toggleTheme(string $theme): Response
    {
        $this->get('session')->set('theme', $theme);
        
        return $this->redirect($this->generateUrl('app_home'));
    }
}