<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * @return Permission[] Returns an array of Permission objects
     */
    public function findByRole($roleId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.roles', 'r')
            ->andWhere('r.id_role = :roleId')
            ->setParameter('roleId', $roleId)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findPublicPermissions(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.public_access = true')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}