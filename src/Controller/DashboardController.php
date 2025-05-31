<?php 

namespace App\Controller;

use App\Entity\Equipement;
use App\Entity\Licence;
use App\Entity\Panne;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $nbEquipements = $em->getRepository(Equipement::class)->count([]);
        $nbUtilisateurs = $em->getRepository(Utilisateur::class)->count([]);
        $nbPannesActives = $em->getRepository(Panne::class)->count(['statut' => 'en cours']);
        $nbLicences = $em->getRepository(Licence::class)->count([]);
         
        $connection = $em->getConnection();
        $sql = "
            SELECT 
                MONTH(date_signalement) AS mois,
                COUNT(id) AS total
            FROM panne
            WHERE date_signalement IS NOT NULL
            GROUP BY mois
            ORDER BY mois
        ";
        $stmt = $connection->prepare($sql);
        $pannesParMois = $stmt->executeQuery()->fetchAllAssociative();



        return $this->render('dashboard/index.html.twig', [
            'nbEquipements' => $nbEquipements,
            'nbUtilisateurs' => $nbUtilisateurs,
            'nbPannesActives' => $nbPannesActives,
            'nbLicences' => $nbLicences,
            'pannesParMois' => $pannesParMois,
        ]);
    }
}
