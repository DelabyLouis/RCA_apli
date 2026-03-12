<?php

namespace App\Repository;

use App\Entity\Entreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entreprise>
 */
class EntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entreprise::class);
    }

    /**
     * Récupère les entreprises ayant au moins une transaction, triées par nom
     * @return Entreprise[]
     */
    public function findEntreprisesWithTransactions(): array
    {
        try {
            return $this->createQueryBuilder('e')
                ->leftJoin('e.transactions', 't')
                ->where('t.id_transaction IS NOT NULL')
                ->groupBy('e.id_entreprise')
                ->orderBy('e.nomEntreprise', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner toutes les entreprises
            return $this->findAll();
        }
    }

    //    /**
    //     * @return Entreprise[] Returns an array of Entreprise objects
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

    //    public function findOneBySomeField($value): ?Entreprise
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
