<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Récupère le dernier numéro d'ordre utilisé pour un exercice donné
     */
    public function getLastNumeroOrdreByExercice(int $exerciceId): ?int
    {
        error_log("[TransactionRepository::getLastNumeroOrdreByExercice] Exercice ID: {$exerciceId}");
        
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.numero_ordre)')
            ->where('t.exercice = :exerciceId')
            ->setParameter('exerciceId', $exerciceId)
            ->getQuery()
            ->getSingleScalarResult();
        
        $max = $result ? (int) $result : 0;
        error_log("[TransactionRepository::getLastNumeroOrdreByExercice] Result: {$max}");
        return $max;
    }

    /**
     * Alias pour getLastNumeroOdreByExercice
     */
    public function getMaxNumeroOrdreForExercice($exercice): int
    {
        $exerciceId = is_object($exercice) ? $exercice->getIdExercice() : $exercice;
        error_log("[TransactionRepository::getMaxNumeroOrdreForExercice] Exercice ID: {$exerciceId}");
        $max = $this->getLastNumeroOrdreByExercice($exerciceId);
        error_log("[TransactionRepository::getMaxNumeroOrdreForExercice] Result: {$max}");
        return $max;
    }

    /**
     * Récupère le dernier numéro d'ordre utilisé (toutes exercices confondus)
     * @deprecated Utiliser getLastNumeroOrdreByExercice() pour les nouvelles transactions
     */
    public function getLastNumeroOrdre(): ?int
    {
        error_log("[TransactionRepository::getLastNumeroOrdre] Getting last numero_ordre across all exercices");
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.numero_ordre)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int) $result : 0;
    }

    /**
     * Calcule le solde total d'un exercice
     */
    public function calculateSoldeByExercice(int $exerciceId): float
    {
        // Calculer le solde en prenant en compte que les transactions livret 
        // doivent être inversées pour le point de vue du compte courant
        $transactions = $this->createQueryBuilder('t')
            ->select('t.montant, t.type_compte')
            ->where('t.exercice = :exerciceId')
            ->setParameter('exerciceId', $exerciceId)
            ->getQuery()
            ->getResult();
        
        $solde = 0.0;
        foreach ($transactions as $transaction) {
            $montant = (float) $transaction['montant'];
            // Inverser le montant pour les transactions livret
            if ($transaction['type_compte'] === 'livret') {
                $montant = -$montant;
            }
            $solde += $montant;
        }
        
        return $solde;
    }

    /**
     * Calcule le solde du compte courant
     * Inclut toutes les transactions, avec les transactions livret inversées
     * (même logique que la colonne "montant" de la page transaction)
     */
    public function calculerSoldeCompteCourant(): float
    {
        // Transactions normales (compte courant)
        $resultNormal = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->where('t.type_compte != :livret OR t.type_compte IS NULL')
            ->setParameter('livret', 'livret')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Transactions livret (inversées pour le point de vue du compte courant)
        $resultLivret = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->where('t.type_compte = :livret')
            ->setParameter('livret', 'livret')
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float) (($resultNormal ?? 0) - ($resultLivret ?? 0));
    }

    /**
     * Calcule le solde du livret
     */
    public function calculerSoldeLivret(): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->where('t.type_compte = :type')
            ->setParameter('type', 'livret')
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float) ($result ?? 0);
    }

    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}