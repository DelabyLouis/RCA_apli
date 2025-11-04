<?php

namespace App\Repository;

use App\Entity\AuditTrail;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditTrail>
 */
class AuditTrailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditTrail::class);
    }

    /**
     * Trouve les audits pour un utilisateur donné
     */
    public function findByUser(User $user, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les audits pour une entité donnée
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.entity_type = :type')
            ->andWhere('a.entity_id = :id')
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les audits par action
     */
    public function findByAction(string $action, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les audits critiques récents
     */
    public function findCriticalRecent(\DateTime $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.severity = :severity')
            ->setParameter('severity', 'critical');

        if ($since) {
            $qb->andWhere('a.created_at >= :since')
               ->setParameter('since', $since);
        }

        return $qb->orderBy('a.created_at', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Nettoie les anciens audits (RGPD - conservation limitée)
     */
    public function cleanupOldAudits(\DateTime $beforeDate): int
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->andWhere('a.created_at < :date')
            ->setParameter('date', $beforeDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Statistiques d'accès par utilisateur
     */
    public function getAccessStatsByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.action, COUNT(a.id) as count')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->groupBy('a.action')
            ->getQuery()
            ->getResult();
    }
}