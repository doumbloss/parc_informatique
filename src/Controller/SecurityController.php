<?php

namespace App\Controller;

use Symfony\Component\Routing\RouterInterface;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Form\AddAdminType; // Nouveau formulaire
use App\Form\ProfileFormType; // Nouveau formulaire
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, RouterInterface $router): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $hasForgotPasswordRoute = false;
        try {
            $router->generate('app_forgot_password');
            $hasForgotPasswordRoute = true;
        } catch (\Exception $e) {}

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'has_forgot_password_route' => $hasForgotPasswordRoute,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ReCaptcha $reCaptcha
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $recaptcha = $reCaptcha->verify($recaptchaResponse, $request->getClientIp());
            if (!$recaptcha->isSuccess()) {
                $this->addFlash('error', 'Vérification reCAPTCHA échouée.');
                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                    'reCAPTCHA_enabled' => true,
                    'recaptcha_site_key' => $this->getParameter('recaptcha_site_key'),
                ]);
            }

            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                return $this->redirectToRoute('app_register');
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_USER']);

            $utilisateur = new Utilisateur();
            $utilisateur->setNom($user->getFullName());
            $utilisateur->setEmail($user->getEmail());
            $utilisateur->setRole('USER');

            $user->setUtilisateur($utilisateur);
            $utilisateur->setUser($user);

            $em->persist($utilisateur);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé ! Connectez-vous.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'reCAPTCHA_enabled' => true,
            'recaptcha_site_key' => $this->getParameter('recaptcha_site_key'),
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        CsrfTokenManagerInterface $csrfTokenManager,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $csrfToken = $request->request->get('_csrf_token');

            if (!$this->isCsrfTokenValid('forgot_password', $csrfToken)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_forgot_password');
            }

            if ($email) {
                $user = $userRepository->findOneBy(['email' => $email]);
                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $user->setResetToken($token);
                    $user->setResetTokenExpiresAt((new \DateTime())->modify('+1 hour'));

                    $em->persist($user);
                    $em->flush();

                    $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                    $email = (new Email())
                        ->from('no-reply@votre-domaine.com')
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe')
                        ->html($this->renderView('security/reset_password_email.html.twig', [
                            'resetLink' => $resetLink,
                            'user' => $user,
                        ]));

                    $mailer->send($email);
                }
            }

            $this->addFlash('info', 'Si cette adresse existe, un lien a été envoyé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'csrf_token' => $csrfTokenManager->getToken('forgot_password')->getValue(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        Request $request,
        string $token,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = $userRepository->findOneBy(['resetToken' => $token]);
        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            if ($password && strlen($password) >= 8) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);

                $em->flush();

                $this->addFlash('success', 'Mot de passe réinitialisé.');
                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('error', 'Le mot de passe doit contenir 8 caractères minimum.');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }

   #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
public function profile(
    Request $request,
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher
): Response {
    $this->denyAccessUnlessGranted('ROLE_USER');

    $user = $this->getUser();
    $utilisateur = $user->getUtilisateur();

    $form = $this->createForm(ProfileFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $currentPassword = $form->get('currentPassword')->getData();
        $plainPassword = $form->get('plainPassword')->getData();

        // Vérifier l'ancien mot de passe
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_profile');
        }

        // Mettre à jour le mot de passe si un nouveau est fourni
        if ($plainPassword) {
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        // Mettre à jour les champs de Utilisateur si nécessaire
        if ($utilisateur) {
            $utilisateur->setNom($user->getFullName());
            $utilisateur->setEmail($user->getEmail());
            $entityManager->persist($utilisateur);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
        return $this->redirectToRoute('app_profile');
    }

    return $this->render('security/profile.html.twig', [
        'user' => $user,
        'utilisateur' => $utilisateur,
        'form' => $form->createView(),
    ]);
}

    #[Route('/admin/users', name: 'app_admin_users')]
    public function adminUsers(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin/user/{id}/toggle-role', name: 'app_admin_toggle_role', methods: ['POST'])]
    public function toggleUserRole(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('toggle_role_' . $request->request->getId(), $request->request->get('_token'))) {
            $role = $request->request->get('role', 'ROLE_ADMIN');
            $currentRoles = $user->getRoles();

            if (in_array($role, $currentRoles)) {
                $newRoles = array_diff($currentRoles, [$role]);
                $action = 'retiré';
            } else {
                $newRoles = array_unique(array_merge($currentRoles, [$role]));
                $action = 'accordé';
            }

            $user->setRoles(array_values($newRoles));
            $entityManager->flush();

            $this->addFlash('success', sprintf('Rôle %s %s à %s', $role, $action, $user->getFullName()));
        }

        return $this->redirectToRoute('app_admin_users');
    }


    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    #[Route('/csrf-token', name: 'app_csrf_token', methods: ['GET'])]
    public function getCsrfToken(Request $request): JsonResponse
    {
        $intent = $request->query->get('intent');
        $panneId = $request->query->get('panne_id');
        $token = $this->csrfTokenManager->getToken("resolve{$panneId}")->getValue();
        return new JsonResponse(['token' => $token]);
    }

    #[Route('/admin/add-admin', name: 'app_admin_add_admin', methods: ['GET', 'POST'])]
        public function addAdmin(
            Request $request,
            UserPasswordHasherInterface $passwordHasher,
            EntityManagerInterface $entityManager
        ): Response {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $form = $this->createForm(AddAdminType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
                if ($existingUser) {
                    $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                    return $this->redirectToRoute('app_admin_add_admin');
                }

                $user = new User();
                $utilisateur = new Utilisateur();
                $utilisateur->setUser($user);

                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setEmail($data['email']);
                $user->setFirstName($data['firstName']);
                $user->setLastName($data['lastName']);
                $user->setIsAdmin(true);
                $user->setPassword($hashedPassword);

                $utilisateur->setNom($data['firstName'] . ' ' . $data['lastName']);
                $utilisateur->setEmail($data['email']);
                $utilisateur->setFonction('Administrateur');
                $utilisateur->setService('Administration');
                $utilisateur->setTelephone('123456789');
                $utilisateur->setRole('admin');

                $entityManager->persist($user);
                $entityManager->persist($utilisateur);
                $entityManager->flush();

                $this->addFlash('success', 'Administrateur ' . $data['email'] . ' ajouté avec succès.');
                return $this->redirectToRoute('app_admin_users');
            }

            return $this->render('security/add_admin.html.twig', [
                'form' => $form->createView(),
            ]);
        }


     #[Route('/admin/user/{id}/delete', name: 'app_admin_delete_user', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Protéger l'admin initial
        if ($user->getEmail() === 'doumbouyadmin@gmail.com') {
            $this->addFlash('error', 'L\'administrateur initial ne peut pas être supprimé.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur ' . $user->getFullName() . ' supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}