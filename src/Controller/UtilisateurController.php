<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\HistoriqueLoggerService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/utilisateur')]
#[IsGranted('ROLE_ADMIN')] // Seuls les admins peuvent accéder à cette section
class UtilisateurController extends AbstractController
{
    private HistoriqueLoggerService $historiqueLogger;

    public function __construct(HistoriqueLoggerService $historiqueLogger)
    {
        $this->historiqueLogger = $historiqueLogger;
    }

    #[Route('/', name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        // Log de l'accès à la liste des utilisateurs
        $this->historiqueLogger->logAction(
            'CONSULTATION',
            'Liste des utilisateurs consultée',
            $this->getUser()
        );

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            // Log de la création
            $this->historiqueLogger->logAction(
                'CREATION',
                'Utilisateur créé: ' . $utilisateur->getNom(),
                $this->getUser(),
                $utilisateur->getId()
            );

            $this->addFlash('success', 'Utilisateur créé avec succès.');

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        // Vérifier si l'utilisateur peut voir ce profil
        $this->checkUserAccess($utilisateur);

        // Log de la consultation
        $this->historiqueLogger->logAction(
            'CONSULTATION',
            'Profil utilisateur consulté: ' . $utilisateur->getNom(),
            $this->getUser(),
            $utilisateur->getId()
        );

        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur peut modifier ce profil
        $this->checkUserAccess($utilisateur);

        $originalData = [
            'nom' => $utilisateur->getNom(),
            'fonction' => $utilisateur->getFonction(),
            'service' => $utilisateur->getService(),
            'role' => $utilisateur->getRole()
        ];

        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log des modifications
            $modifications = $this->getModifications($originalData, $utilisateur);
            if (!empty($modifications)) {
                $this->historiqueLogger->logAction(
                    'MODIFICATION',
                    'Utilisateur modifié: ' . implode(', ', $modifications),
                    $this->getUser(),
                    $utilisateur->getId()
                );
            }

            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur connecté est l'admin principal
        $isMainAdmin = $currentUser && $currentUser->getEmail() === 'doumbouyadmin@gmail.com';

        // Autoriser la suppression si l'utilisateur est l'admin principal ou un super admin
        if (!$isMainAdmin && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour supprimer un utilisateur.');
        }

        // Protéger l'admin principal contre la suppression
        if ($utilisateur->getUser() && $utilisateur->getUser()->getEmail() === 'doumbouyadmin@gmail.com') {
            $this->addFlash('error', 'L\'administrateur principal ne peut pas être supprimé.');
            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete' . $utilisateur->getId(), $request->request->get('_token'))) {
            $nom = $utilisateur->getNom();
            $user = $utilisateur->getUser();

            $entityManager->remove($utilisateur);
            if ($user) {
                $entityManager->remove($user);
            }
            $entityManager->flush();

            // Log de la suppression
            $this->historiqueLogger->logAction(
                'SUPPRESSION',
                'Utilisateur supprimé: ' . $nom,
                $currentUser
            );

            $this->addFlash('success', 'Utilisateur ' . $nom . ' supprimé avec succès.');
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/profile/my', name: 'app_utilisateur_my_profile', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')] // Tous les utilisateurs connectés peuvent voir leur profil
    public function myProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $utilisateur = $user->getUtilisateur();

        if (!$utilisateur) {
            throw $this->createNotFoundException('Profil utilisateur non trouvé.');
        }

        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'allow_role_change' => false // Empêcher la modification du rôle par l'utilisateur lui-même
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->historiqueLogger->logAction(
                'MODIFICATION',
                'Profil personnel modifié',
                $this->getUser(),
                $utilisateur->getId()
            );

            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('app_utilisateur_my_profile');
        }

        return $this->render('utilisateur/my_profile.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Vérifie si l'utilisateur connecté peut accéder aux données d'un autre utilisateur
     */
    private function checkUserAccess(Utilisateur $utilisateur): void
    {
        $currentUser = $this->getUser();
        
        // Les admins peuvent tout voir
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        // Les utilisateurs normaux ne peuvent voir que leur propre profil
        if ($currentUser->getUtilisateur() !== $utilisateur) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à ce profil.');
        }
    }

    /**
     * Compare les données originales avec les nouvelles pour détecter les modifications
     */
    private function getModifications(array $originalData, Utilisateur $utilisateur): array
    {
        $modifications = [];

        if ($originalData['nom'] !== $utilisateur->getNom()) {
            $modifications[] = "Nom: {$originalData['nom']} → {$utilisateur->getNom()}";
        }
        if ($originalData['fonction'] !== $utilisateur->getFonction()) {
            $modifications[] = "Fonction: {$originalData['fonction']} → {$utilisateur->getFonction()}";
        }
        if ($originalData['service'] !== $utilisateur->getService()) {
            $modifications[] = "Service: {$originalData['service']} → {$utilisateur->getService()}";
        }
        if ($originalData['role'] !== $utilisateur->getRole()) {
            $modifications[] = "Rôle: {$originalData['role']} → {$utilisateur->getRole()}";
        }

        return $modifications;
    }
}