<?php

namespace App\Repository;

use App\Entity\AuditSuppression;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditSuppression>
 */
class AuditSuppressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditSuppression::class);
    }

    /**
     * Trouve les entités supprimées d'un type donné
     */
    public function findDeletedEntitiesByType(string $entityType): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.entity_type = :type')
            ->setParameter('type', $entityType)
            ->orderBy('a.deleted_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les entités programmées pour suppression définitive
     */
    public function findScheduledForHardDelete(\DateTime $beforeDate = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.scheduled_hard_delete IS NOT NULL');

        if ($beforeDate) {
            $qb->andWhere('a.scheduled_hard_delete <= :date')
               ->setParameter('date', $beforeDate);
        }

        return $qb->orderBy('a.scheduled_hard_delete', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Récupère l'audit de suppression d'une entité spécifique
     */
    public function findByEntityTypeAndId(string $entityType, int $entityId): ?AuditSuppression
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.entity_type = :type')
            ->andWhere('a.entity_id = :id')
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId)
            ->orderBy('a.deleted_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}