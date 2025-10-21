<?php

namespace App\Repository;

use App\Entity\HistoriqueCloture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueCloture>
 */
class HistoriqueCloturRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueCloture::class);
    }

    /**
     * Récupère l'historique des clôtures/déclôtures pour un exercice donné
     * 
     * @param int $exerciceId
     * @return HistoriqueCloture[]
     */
    public function findByExerciceOrderByDate(int $exerciceId): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exercice = :exerciceId')
            ->setParameter('exerciceId', $exerciceId)
            ->orderBy('h.date_action', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la dernière action (clôture ou déclôture) pour un exercice
     * 
     * @param int $exerciceId
     * @return HistoriqueCloture|null
     */
    public function findLastActionByExercice(int $exerciceId): ?HistoriqueCloture
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exercice = :exerciceId')
            ->setParameter('exerciceId', $exerciceId)
            ->orderBy('h.date_action', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère l'historique récent des clôtures/déclôtures (toutes exercices confondus)
     * 
     * @param int $limit
     * @return HistoriqueCloture[]
     */
    public function findRecentHistorique(int $limit = 10): array
    {
        return $this->createQueryBuilder('h')
            ->join('h.exercice', 'e')
            ->orderBy('h.date_action', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return HistoriqueCloture[] Returns an array of HistoriqueCloture objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?HistoriqueCloture
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}