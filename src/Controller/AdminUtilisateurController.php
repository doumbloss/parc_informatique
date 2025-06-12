<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use ReCaptcha\ReCaptcha;

#[Route('/admin/utilisateur')]
class AdminUtilisateurController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em = $em;
        $this->hasher = $hasher;
    }

    #[Route('/new', name: 'app_admin_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $utilisateur = new Utilisateur();
        $utilisateur->setUser($user);

        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'attr' => ['role_editable' => false],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->hasher->hashPassword($user, 'defaultpassword');
            $user->setEmail($utilisateur->getEmail());
            $user->setIsAdmin(false);
            $user->setPassword($password);

            $this->em->persist($user);
            $this->em->persist($utilisateur);
            $this->em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_admin_utilisateur_index');
        }

        return $this->render('admin/utilisateur/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new-admin', name: 'app_admin_utilisateur_new_admin', methods: ['GET', 'POST'])]
    public function newAdmin(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $utilisateur = new Utilisateur();
        $utilisateur->setUser($user);

        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'attr' => ['role_editable' => true],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->hasher->hashPassword($user, 'defaultpassword');
            $user->setEmail($utilisateur->getEmail());
            $user->setIsAdmin($utilisateur->getRole() === 'admin');
            $user->setPassword($password);

            $this->em->persist($user);
            $this->em->persist($utilisateur);
            $this->em->flush();

            $this->addFlash('success', 'Administrateur créé avec succès.');
            return $this->redirectToRoute('app_admin_utilisateur_index');
        }

        return $this->render('admin/utilisateur/new_admin.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('', name: 'app_admin_utilisateur_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/toggle-role/{id}', name: 'app_admin_toggle_role', methods: ['POST'])]
    public function toggleRole(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('toggle_role_' . $user->getId(), $request->request->get('_token'))) {
            $currentRoles = $user->getRoles();
            $targetRole = 'ROLE_ADMIN';

            if (in_array($targetRole, $currentRoles)) {
                $user->setRoles(array_diff($currentRoles, [$targetRole]));
                $user->setIsAdmin(false);
                $this->addFlash('success', sprintf('Le rôle %s a été retiré de %s.', $targetRole, $user->getFullName()));
            } else {
                $user->setRoles(array_merge($currentRoles, [$targetRole]));
                $user->setIsAdmin(true);
                $this->addFlash('success', sprintf('Le rôle %s a été ajouté à %s.', $targetRole, $user->getFullName()));
            }

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('app_admin_utilisateur_index');
        }

        $this->addFlash('danger', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_admin_utilisateur_index');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ReCaptcha $reCaptcha
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
            $user->setIsAdmin(false);

            $utilisateur = new Utilisateur();
            $utilisateur->setNom($user->getFullName());
            $utilisateur->setEmail($user->getEmail());
            $utilisateur->setRole('user');

            $user->setUtilisateur($utilisateur);
            $utilisateur->setUser($user);

            $em->persist($utilisateur);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé ! Connectez-vous.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'reCAPTCHA_enabled' => true,
            'recaptcha_site_key' => $this->getParameter('recaptcha_site_key'),
        ]);
    }
}