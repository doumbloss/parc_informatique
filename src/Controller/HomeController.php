<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Security $security): Response
    {
        if ($security->getUser()) {
            // Utilisateur connecté → vers dashboard
            return $this->redirectToRoute('app_dashboard');
 
        }

        // Utilisateur non connecté → page publique
        return $this->render('home/public.html.twig');
         
    }
}
