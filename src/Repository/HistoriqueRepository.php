<?php

namespace App\Repository;

use App\Entity\Historique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Historique>
 */
class HistoriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Historique::class);
    }

    /**
     * Trouve les historiques par mois
     */
     public function findByMonth(int $annee, int $mois): array
    {
        $debut = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $annee, $mois));
        $fin = $debut->modify('first day of next month');

        return $this->createQueryBuilder('h')
            ->where('h.dateEvenement >= :debut')
            ->andWhere('h.dateEvenement < :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('h.dateEvenement', 'DESC')
            ->getQuery()
            ->getResult();
    }



    /**
     * Trouve les historiques par année
     */
    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('h')
            ->where('YEAR(h.dateEvenement) = :year')
            ->setParameter('year', $year)
            ->orderBy('h.dateEvenement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les historiques par période
     */
    public function findByPeriod(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.dateEvenement BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('h.dateEvenement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les historiques récents
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.dateEvenement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les historiques par type d'événement
     */
    public function findByTypeEvenement(string $type): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.typeEvenement = :type')
            ->setParameter('type', $type)
            ->orderBy('h.dateEvenement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les historiques par équipement
     */
    public function findByEquipement(int $equipementId): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.equipement = :equipement')
            ->setParameter('equipement', $equipementId)
            ->orderBy('h.dateEvenement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par mois pour l'année courante
     */
    public function getStatistiquesParMois(int $year = null): array
    {
        $year = $year ?? date('Y');
        
        return $this->createQueryBuilder('h')
            ->select('MONTH(h.dateEvenement) as mois, COUNT(h.id) as total')
            ->where('YEAR(h.dateEvenement) = :year')
            ->setParameter('year', $year)
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les événements par type pour une période
     */
    public function countByTypeAndPeriod(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('h')
            ->select('h.typeEvenement, COUNT(h.id) as total')
            ->where('h.dateEvenement BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('h.typeEvenement')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }
}