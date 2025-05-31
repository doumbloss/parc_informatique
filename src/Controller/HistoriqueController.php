<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Form\HistoriqueType;
use App\Repository\HistoriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DoctrineExtensions\Query\Mysql\Month;
use DoctrineExtensions\Query\Mysql\Year;
use DoctrineExtensions\Query\Mysql;
use App\Service\HistoriqueLoggerService;


#[Route('/historique')]
class HistoriqueController extends AbstractController
{
    #[Route('/', name: 'app_historique_index', methods: ['GET'])]
    public function index(HistoriqueRepository $historiqueRepository): Response
    {
        // Exemple : afficher les historiques de mai 2025
        $historiques = $historiqueRepository->findByMonth(2025, 5);

        return $this->render('historique/index.html.twig', [
            'historiques' => $historiques,
        ]);
    }

   #[Route('/stats', name: 'app_historique_stats')]
    public function stats(HistoriqueRepository $historiqueRepository): Response
    {
        $stats = $historiqueRepository->getStatisticsByMonthNative(); // ou getStatisticsByMonth()

        return $this->render('historique/stats.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/mois/{annee}/{mois}', name: 'app_historique_par_mois', methods: ['GET'])]
    public function parMois(int $annee, int $mois, HistoriqueRepository $historiqueRepository): Response
    {
        $historiques = $historiqueRepository->findByMonth($annee, $mois);

        return $this->render('historique/index.html.twig', [
            'historiques' => $historiques,
            'mois' => $mois,
            'annee' => $annee,
        ]);
    }

    #[Route('/new', name: 'app_historique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $historique = new Historique();
        $form = $this->createForm(HistoriqueType::class, $historique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($historique);
            $entityManager->flush();

            return $this->redirectToRoute('app_historique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('historique/new.html.twig', [
            'historique' => $historique,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_historique_show', methods: ['GET'])]
    public function show(Historique $historique): Response
    {
        return $this->render('historique/show.html.twig', [
            'historique' => $historique,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_historique_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Historique $historique, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HistoriqueType::class, $historique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_historique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('historique/edit.html.twig', [
            'historique' => $historique,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_historique_delete', methods: ['POST'])]
    public function delete(Request $request, Historique $historique, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $historique->getId(), $request->request->get('_token'))) {
            $entityManager->remove($historique);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_historique_index', [], Response::HTTP_SEE_OTHER);
    }
}
