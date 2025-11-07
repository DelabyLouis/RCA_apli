<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;

class PaginationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Crée un paginateur optimisé pour les grandes collections
     */
    public function createPaginator(
        QueryBuilder $queryBuilder, 
        int $page = 1, 
        int $limit = 20
    ): Paginator {
        $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($queryBuilder);
        
        // Optimisation : ne pas récupérer les jointures si pas nécessaire
        $paginator->setUseOutputWalkers(false);
        
        return $paginator;
    }

    /**
     * Calcule le nombre total de pages
     */
    public function getTotalPages(Paginator $paginator, int $limit): int
    {
        return (int) ceil($paginator->count() / $limit);
    }

    /**
     * Optimise une requête avec mise en cache
     */
    public function getCachedResult(string $cacheKey, callable $queryCallback, int $ttl = 3600): mixed
    {
        // Utilisation du cache de résultats Doctrine
        $cache = $this->entityManager->getConfiguration()->getResultCache();
        
        if ($cache) {
            $cacheItem = $cache->getItem($cacheKey);
            
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = $queryCallback();
            
            $cacheItem->set($result);
            $cacheItem->expiresAfter($ttl);
            $cache->save($cacheItem);
            
            return $result;
        }

        return $queryCallback();
    }
}