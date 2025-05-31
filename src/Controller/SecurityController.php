<?php

namespace App\Controller;

use Symfony\Component\Routing\RouterInterface;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, RouterInterface $router): Response
    {
        // Rediriger si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard'); // Remplacez par votre route de dashboard
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Vérifier si la route app_forgot_password existe
        $hasForgotPasswordRoute = false;
        try {
            $router->generate('app_forgot_password');
            $hasForgotPasswordRoute = true;
        } catch (\Exception $e) {
            // La route n'existe pas
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'has_forgot_password_route' => $hasForgotPasswordRoute,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        // Rediriger si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'email existe déjà
            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                return $this->redirectToRoute('app_register');
            }

            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            // Définir les rôles par défaut
            $user->setRoles(['ROLE_USER']);

            // Persister l'utilisateur
            $em->persist($user);
            $em->flush();

            // Message de succès
            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        // Si l'utilisateur est connecté, pas besoin de récupérer son mot de passe
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Ici vous pouvez ajouter la logique pour envoyer un email de récupération
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            // TODO: Implémenter la logique d'envoi d'email
            // Exemple : envoi d'un token de récupération par email
            
            $this->addFlash('info', 'Si cette adresse email existe dans notre système, vous recevrez un lien de récupération.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }
}