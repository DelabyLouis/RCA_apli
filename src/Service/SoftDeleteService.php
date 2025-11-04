<?php

namespace App\Service;

use App\Entity\AuditSuppression;
use App\Entity\User;
use App\Repository\AuditSuppressionRepository;
use App\Trait\SoftDeletableTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\SerializerInterface;

class SoftDeleteService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditSuppressionRepository $auditRepository,
        private SerializerInterface $serializer,
        private RequestStack $requestStack,
        private Security $security
    ) {}

    /**
     * Effectue une suppression soft avec audit
     */
    public function softDelete(
        object $entity, 
        string $reason = null, 
        ?User $deletedBy = null, 
        bool $scheduleHardDelete = true
    ): AuditSuppression {
        // Vérifier que l'entité supporte le soft delete
        if (!$this->supportsSoftDelete($entity)) {
            throw new \InvalidArgumentException(
                'L\'entité ' . get_class($entity) . ' ne supporte pas le soft delete'
            );
        }

        // Obtenir l'ID avant suppression
        $entityId = $this->getEntityId($entity);
        
        // Sérialiser les données avant suppression
        $entityData = $this->serializer->serialize($entity, 'json');
        
        // Effectuer le soft delete
        $entity->delete();
        
        // Créer l'audit
        $audit = new AuditSuppression();
        $audit->setEntityType(get_class($entity));
        $audit->setEntityId($entityId);
        $audit->setEntityData($entityData);
        $audit->setDeletedBy($deletedBy ?? $this->security->getUser());
        $audit->setDeletedAt(new \DateTime());
        $audit->setDeletionReason($reason);
        $audit->setDeletionType('soft');
        
        // Enregistrer l'IP si disponible
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $audit->setIpAddress($request->getClientIp());
        }
        
        // Programmer la suppression définitive (par exemple dans 3 ans pour conformité RGPD)
        if ($scheduleHardDelete) {
            $hardDeleteDate = new \DateTime('+3 years');
            $audit->setScheduledHardDelete($hardDeleteDate);
        }
        
        // Persister l'audit et l'entité
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        
        return $audit;
    }

    /**
     * Restaure une entité supprimée en soft delete
     */
    public function restore(object $entity, string $reason = null): void
    {
        if (!$this->supportsSoftDelete($entity)) {
            throw new \InvalidArgumentException(
                'L\'entité ' . get_class($entity) . ' ne supporte pas le soft delete'
            );
        }
        
        if (!$entity->isDeleted()) {
            throw new \LogicException('L\'entité n\'est pas supprimée');
        }
        
        // Restaurer l'entité
        $entity->restore();
        
        // Créer un audit de restauration
        $audit = new AuditSuppression();
        $audit->setEntityType(get_class($entity));
        $audit->setEntityId($this->getEntityId($entity));
        $audit->setEntityData('RESTAURATION');
        $audit->setDeletedBy($this->security->getUser());
        $audit->setDeletedAt(new \DateTime());
        $audit->setDeletionReason($reason ?? 'Restauration de l\'entité');
        $audit->setDeletionType('restore');
        
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $audit->setIpAddress($request->getClientIp());
        }
        
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
    }

    /**
     * Effectue une suppression définitive avec audit (pour demandes RGPD)
     */
    public function hardDelete(object $entity, string $reason = 'Demande RGPD'): AuditSuppression
    {
        $entityId = $this->getEntityId($entity);
        $entityData = $this->serializer->serialize($entity, 'json');
        
        // Créer l'audit AVANT la suppression
        $audit = new AuditSuppression();
        $audit->setEntityType(get_class($entity));
        $audit->setEntityId($entityId);
        $audit->setEntityData($entityData);
        $audit->setDeletedBy($this->security->getUser());
        $audit->setDeletedAt(new \DateTime());
        $audit->setDeletionReason($reason);
        $audit->setDeletionType('hard');
        
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $audit->setIpAddress($request->getClientIp());
        }
        
        // Persister l'audit
        $this->entityManager->persist($audit);
        
        // Effectuer la suppression définitive
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        
        return $audit;
    }

    /**
     * Vérifie si une entité supporte le soft delete
     */
    private function supportsSoftDelete(object $entity): bool
    {
        $reflection = new \ReflectionClass($entity);
        $traits = $reflection->getTraitNames();
        
        return in_array(SoftDeletableTrait::class, $traits);
    }

    /**
     * Récupère l'ID d'une entité
     */
    private function getEntityId(object $entity): int
    {
        $reflection = new \ReflectionClass($entity);
        
        // Chercher une méthode getId() ou similaire
        $methods = ['getId', 'getIdUser', 'getIdPersonne', 'getIdEntreprise', 'getIdTransaction'];
        
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                return $entity->$method();
            }
        }
        
        throw new \LogicException('Impossible de déterminer l\'ID de l\'entité ' . get_class($entity));
    }

    /**
     * Récupère les entités supprimées avec possibilité de restauration
     */
    public function getDeletedEntities(string $entityType): array
    {
        return $this->auditRepository->findDeletedEntitiesByType($entityType);
    }

    /**
     * Programme le nettoyage automatique des entités supprimées
     */
    public function scheduleAutomaticCleanup(): array
    {
        $toCleanup = $this->auditRepository->findScheduledForHardDelete(new \DateTime());
        
        // TODO: Implémenter la suppression automatique via une commande console
        // ou un job en arrière-plan
        
        return $toCleanup;
    }
}