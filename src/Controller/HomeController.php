<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
         

        return $this->render('home/home.html.twig');
    }
}

// #[Route('/', name: 'app_home')]
//     public function index(Security $security): Response
//     {
//         if ($security->getUser()) {
//             return $this->redirectToRoute('app_dashboard');
//         }

//         return $this->render('home/home.html.twig');
//     }