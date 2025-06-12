<?php

namespace App\Controller;

use App\Entity\Equipement;
use App\Entity\User;
use App\Entity\Utilisateur;
use App\Entity\Localisation;
use App\Entity\Panne;
use App\Entity\ActivityLog;
use App\Entity\Licence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        $stats = $this->generateStats();
        $recentActivities = $this->getRecentActivities();
        $recentPannes = $this->getRecentPannes();
        $chartData = $this->generateChartData();
        $equipmentTypes = $this->getEquipmentTypes();
        $miniChartData = $this->generateMiniChartData();

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'recentPannes' => $recentPannes,
            'chartData' => $chartData,
            'equipmentTypes' => $equipmentTypes,
            'miniChartData' => $miniChartData,
        ]);
    }

   private function generateStats(): array
    {
        $equipmentRepo = $this->entityManager->getRepository(Equipement::class);
        $userRepo = $this->entityManager->getRepository(User::class);
        $utilisateurRepo = $this->entityManager->getRepository(Utilisateur::class);
        $locationRepo = $this->entityManager->getRepository(Localisation::class);
        $panneRepo = $this->entityManager->getRepository(Panne::class);

        $totalEquipments = $equipmentRepo->count([]);
        $activeEquipments = $equipmentRepo->count(['statut' => 'actif']);
        $totalUsers = $userRepo->count([]) + $utilisateurRepo->count([]);
        $totalLocations = $locationRepo->count([]);
        $totalPannes = $panneRepo->count(['statut' => 'en_cours']);

        // Calcul des tendances (mois actuel vs mois précédent)
        $currentMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');
        
        $equipmentTrend = $equipmentRepo->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.dateAjout >= :currentMonth')
            ->setParameter('currentMonth', $currentMonth)
            ->getQuery()->getSingleScalarResult() - 
            $equipmentRepo->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.dateAjout >= :lastMonth AND e.dateAjout < :currentMonth')
            ->setParameters(['lastMonth' => $lastMonth, 'currentMonth' => $currentMonth])
            ->getQuery()->getSingleScalarResult();

        $userTrend = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :currentMonth') // Remplacez createdAt par le champ correct si nécessaire
            ->setParameter('currentMonth', $currentMonth)
            ->getQuery()->getSingleScalarResult();

        $locationTrend = $locationRepo->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.dateAjout >= :currentMonth') // Ajustez selon le champ de Localisation
            ->setParameter('currentMonth', $currentMonth)
            ->getQuery()->getSingleScalarResult();

        $currentWeek = new \DateTime('monday this week');
        $panneTrend = $panneRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.dateSignalement >= :currentWeek')
            ->setParameter('currentWeek', $currentWeek)
            ->getQuery()->getSingleScalarResult();

        return [
            'totalEquipments' => $totalEquipments,
            'activeEquipments' => $activeEquipments,
            'inactiveEquipments' => $totalEquipments - $activeEquipments,
            'activePercentage' => $totalEquipments > 0 ? round(($activeEquipments / $totalEquipments) * 100) : 0,
            'totalUsers' => $totalUsers,
            'totalLocations' => $totalLocations,
            'totalPannes' => $totalPannes,
            'equipmentTrend' => (int)$equipmentTrend,
            'userTrend' => (int)$userTrend,
            'locationTrend' => (int)$locationTrend,
            'panneTrend' => (int)$panneTrend,
        ];
    }
    private function getRecentActivities(): array
    {
        $activities = $this->entityManager->getRepository(ActivityLog::class)
            ->findBy([], ['createdAt' => 'DESC'], 10);

        return array_map(function($activity) {
            return [
                'action' => $activity->getAction(),
                'createdAt' => $activity->getCreatedAt(),
                'icon' => $this->getActivityIcon($activity->getAction()),
            ];
        }, $activities);
    }

    private function getRecentPannes(): array
    {
        return $this->entityManager->getRepository(Panne::class)
            ->findBy(['statut' => 'en_cours'], ['dateSignalement' => 'DESC'], 5);
    }

    private function generateChartData(): array
    {
        $months = [];
        $equipmentData = [];
        $userData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $months[] = $date->format('M');
            
            $monthStart = clone $date;
            $monthStart->modify('first day of this month')->setTime(0, 0, 0);
            $monthEnd = clone $monthStart;
            $monthEnd->modify('last day of this month')->setTime(23, 59, 59);
            
            // Compter les équipements créés ce mois-ci
            $equipmentCount = $this->entityManager->getRepository(Equipement::class)
                ->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.dateAjout >= :start AND e.dateAjout <= :end') // Remplacé dateCreation par dateAjout
                ->setParameters(['start' => $monthStart, 'end' => $monthEnd])
                ->getQuery()->getSingleScalarResult();
            
            $equipmentData[] = (int)$equipmentCount;
            
            // Compter les utilisateurs créés ce mois-ci
            $userCount = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt >= :start AND u.createdAt <= :end') // Assurez-vous que createdAt existe
                ->setParameters(['start' => $monthStart, 'end' => $monthEnd])
                ->getQuery()->getSingleScalarResult();
            
            $userData[] = (int)$userCount;
        }
        
        return [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Équipements',
                    'data' => $equipmentData,
                    'borderColor' => '#3498db',
                    'backgroundColor' => 'rgba(52, 152, 219, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Utilisateurs',
                    'data' => $userData,
                    'borderColor' => '#e74c3c',
                    'backgroundColor' => 'rgba(231, 76, 60, 0.1)',
                    'fill' => true,
                ]
            ]
        ];
    }
    private function getEquipmentTypes(): array
    {
        $types = $this->entityManager->getRepository(Equipement::class)
            ->createQueryBuilder('e')
            ->select('e.categorie as type, COUNT(e.id) as count') // Remplacé e.type par e.categorie as type
            ->groupBy('e.categorie')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(function($type) {
            return [
                'name' => $type['type'] ?? 'Non défini',
                'count' => (int)$type['count'],
                'color' => $this->getTypeColor($type['type'])
            ];
        }, $types);
    }

    private function generateMiniChartData(): array
{
    $equipmentRepo = $this->entityManager->getRepository(Equipement::class);
    $locationRepo = $this->entityManager->getRepository(Localisation::class);
    $userRepo = $this->entityManager->getRepository(User::class);
    $panneRepo = $this->entityManager->getRepository(Panne::class);

    // Données pour les 7 derniers jours
    $days = [];
    $equipmentData = [];
    $locationData = [];
    $userData = [];
    $pannesData = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = new \DateTime("-$i days");
        $days[] = $date->format('D');

        $dayStart = clone $date;
        $dayStart->setTime(0, 0, 0);
        $dayEnd = clone $date;
        $dayEnd->setTime(23, 59, 59);

        // Compter les équipements ajoutés ce jour
        $equipmentCount = $equipmentRepo->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.dateAjout >= :start AND e.dateAjout <= :end')
            ->setParameters(['start' => $dayStart, 'end' => $dayEnd])
            ->getQuery()->getSingleScalarResult();
        $equipmentData[] = (int)$equipmentCount;

        // Compter les localisations ajoutées ce jour
        $locationCount = $locationRepo->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.dateAjout >= :start AND l.dateAjout <= :end')
            ->setParameters(['start' => $dayStart, 'end' => $dayEnd])
            ->getQuery()->getSingleScalarResult();
        $locationData[] = (int)$locationCount;

        // Compter les utilisateurs créés ce jour
        $userCount = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :start AND u.createdAt <= :end')
            ->setParameters(['start' => $dayStart, 'end' => $dayEnd])
            ->getQuery()->getSingleScalarResult();
        $userData[] = (int)$userCount;

        // Compter les pannes signalées ce jour
        $panneCount = $panneRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.dateSignalement >= :start AND p.dateSignalement <= :end')
            ->setParameters(['start' => $dayStart, 'end' => $dayEnd])
            ->getQuery()->getSingleScalarResult();
        $pannesData[] = (int)$panneCount;
    }

    return [
        'equipment' => $equipmentData,
        'location' => $locationData,
        'user' => $userData,
        'panne' => $pannesData,
        'labels' => $days, // Partagé pour tous les mini-charts
    ];
}

    private function getActivityIcon(string $action): string
    {
        $icons = [
            'create' => 'plus-circle',
            'update' => 'edit',
            'delete' => 'trash-2',
            'login' => 'log-in',
            'logout' => 'log-out',
            'repair' => 'tool',
            'maintenance' => 'settings',
            'default' => 'activity'
        ];

        return $icons[$action] ?? $icons['default'];
    }

    private function getTypeColor(string $type): string
    {
        $colors = [
            'Ordinateur' => '#3498db',
            'Imprimante' => '#e74c3c',
            'Scanner' => '#2ecc71',
            'Réseau' => '#f39c12',
            'Serveur' => '#9b59b6',
            'Téléphone' => '#1abc9c',
            'Tablette' => '#34495e',
            'Autre' => '#95a5a6'
        ];

        return $colors[$type] ?? $colors['Autre'];
    }
}