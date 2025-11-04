<?php

namespace App\Service;

use App\Entity\AuditTrail;
use App\Entity\User;
use App\Repository\AuditTrailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditTrailService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditTrailRepository $auditRepository,
        private RequestStack $requestStack,
        private Security $security
    ) {}

    /**
     * Enregistre une action dans l'audit trail
     */
    public function logAction(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $details = null,
        string $severity = 'info',
        ?User $user = null
    ): AuditTrail {
        $audit = new AuditTrail();
        
        // Utilisateur (connecté ou spécifié)
        $audit->setUser($user ?? $this->security->getUser());
        
        // Action et entité
        $audit->setAction($action);
        if ($entityType) {
            $audit->setEntityType($entityType);
        }
        if ($entityId) {
            $audit->setEntityId($entityId);
        }
        
        // Détails en JSON
        if ($details) {
            $audit->setDetails(json_encode($details, JSON_UNESCAPED_UNICODE));
        }
        
        $audit->setSeverity($severity);
        $audit->setCreatedAt(new \DateTime());
        
        // Informations de la requête HTTP si disponible
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $audit->setIpAddress($request->getClientIp());
            $audit->setUserAgent($request->headers->get('User-Agent'));
            $audit->setSessionId($request->getSession()->getId());
            $audit->setRouteName($request->attributes->get('_route'));
        }
        
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        
        return $audit;
    }

    /**
     * Log d'accès aux données personnelles
     */
    public function logDataAccess(object $entity, string $action = 'view', ?array $additionalDetails = null): void
    {
        $entityType = get_class($entity);
        $entityId = $this->getEntityId($entity);
        
        $details = [
            'entity_class' => $entityType,
            'accessed_fields' => $this->getAccessedFields($entity)
        ];
        
        if ($additionalDetails) {
            $details = array_merge($details, $additionalDetails);
        }
        
        $severity = $this->isPersonalData($entity) ? 'warning' : 'info';
        
        $this->logAction(
            $action . '_personal_data',
            $entityType,
            $entityId,
            $details,
            $severity
        );
    }

    /**
     * Log d'export de données (RGPD)
     */
    public function logDataExport(string $format, array $exportedEntities): void
    {
        $this->logAction(
            'data_export',
            'DataExport',
            null,
            [
                'format' => $format,
                'entities_count' => count($exportedEntities),
                'entities' => array_map(fn($e) => get_class($e), $exportedEntities)
            ],
            'critical'
        );
    }

    /**
     * Log de modification de données personnelles
     */
    public function logDataModification(object $entity, array $changedFields): void
    {
        $this->logDataAccess($entity, 'update', [
            'modified_fields' => $changedFields,
            'modification_type' => 'data_update'
        ]);
    }

    /**
     * Log de suppression de données
     */
    public function logDataDeletion(object $entity, string $deletionType = 'soft'): void
    {
        $this->logDataAccess($entity, 'delete', [
            'deletion_type' => $deletionType,
            'can_be_restored' => $deletionType === 'soft'
        ]);
    }

    /**
     * Récupère les audits pour un utilisateur
     */
    public function getUserAudits(User $user, int $limit = 100): array
    {
        return $this->auditRepository->findByUser($user, $limit);
    }

    /**
     * Récupère les audits pour une entité
     */
    public function getEntityAudits(object $entity): array
    {
        $entityType = get_class($entity);
        $entityId = $this->getEntityId($entity);
        
        return $this->auditRepository->findByEntity($entityType, $entityId);
    }

    /**
     * Nettoie les anciens audits selon la politique de rétention
     */
    public function cleanupOldAudits(int $retentionDays = 365): int
    {
        $cutoffDate = new \DateTime("-{$retentionDays} days");
        return $this->auditRepository->cleanupOldAudits($cutoffDate);
    }

    /**
     * Récupère l'ID d'une entité
     */
    private function getEntityId(object $entity): ?int
    {
        $reflection = new \ReflectionClass($entity);
        $methods = ['getId', 'getIdUser', 'getIdPersonne', 'getIdEntreprise', 'getIdTransaction'];
        
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                return $entity->$method();
            }
        }
        
        return null;
    }

    /**
     * Détermine les champs accessibles d'une entité (pour audit)
     */
    private function getAccessedFields(object $entity): array
    {
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();
        
        $fields = [];
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            if (!in_array($propertyName, ['id', 'id_user', 'id_personne', 'password'])) {
                $fields[] = $propertyName;
            }
        }
        
        return $fields;
    }

    /**
     * Détermine si une entité contient des données personnelles
     */
    private function isPersonalData(object $entity): bool
    {
        $personalDataEntities = [
            'App\\Entity\\Personne',
            'App\\Entity\\User',
            'App\\Entity\\ConsentementRgpd'
        ];
        
        return in_array(get_class($entity), $personalDataEntities);
    }

    // Constantes pour les actions
    public const ACTION_VIEW = 'view';
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_EXPORT = 'export';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_GDPR_REQUEST = 'gdpr_request';
    
    // Constantes pour la sévérité
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';
}