<?php

namespace App\Repository;

use App\Entity\ConsentementRgpd;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConsentementRgpd>
 */
class ConsentementRgpdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsentementRgpd::class);
    }

    /**
     * Récupère le consentement valide d'un utilisateur pour un type donné
     */
    public function getConsentementValide(User $user, string $type): ?ConsentementRgpd
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.type_consentement = :type')
            ->andWhere('c.accepte = :accepte')
            ->andWhere('c.date_retrait IS NULL')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('accepte', true)
            ->orderBy('c.date_consentement', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les consentements d'un utilisateur
     */
    public function getConsentementsByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.date_consentement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur a donné son consentement pour un type donné
     */
    public function hasValidConsent(User $user, string $type): bool
    {
        return $this->getConsentementValide($user, $type) !== null;
    }
}