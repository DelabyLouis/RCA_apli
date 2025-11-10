<?php

namespace App\Repository;

use App\Entity\Exercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercice>
 */
class ExerciceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercice::class);
    }

    /**
     * Récupère le dernier numéro d'ordre utilisé pour les exercices
     */
    public function getLastNumeroOrdre(): ?int
    {
        $result = $this->createQueryBuilder('e')
            ->select('MAX(e.numero_ordre)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int) $result : 0;
    }

    /**
     * Trouve l'exercice précédent basé sur le numéro d'ordre
     */
    public function findPreviousExercice(Exercice $exercice): ?Exercice
    {
        return $this->createQueryBuilder('e')
            ->where('e.numero_ordre < :numero_ordre')
            ->setParameter('numero_ordre', $exercice->getNumeroOrdre())
            ->orderBy('e.numero_ordre', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les exercices ouverts (non clôturés) triés par libellé descendant
     */
    public function findExercicesOuverts(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.clos = :clos')
            ->setParameter('clos', false)
            ->orderBy('e.libelle', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les exercices triés par numéro d'ordre
     */
    public function findAllOrderedByNumeroOrdre(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.numero_ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Exercice[] Returns an array of Exercice objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Exercice
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}