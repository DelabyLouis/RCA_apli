<?php

namespace App\Entity;

use App\Repository\AuditSuppressionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditSuppressionRepository::class)]
class AuditSuppression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $entity_type = null; // Nom de la classe d'entité

    #[ORM\Column]
    private ?int $entity_id = null; // ID de l'entité supprimée

    #[ORM\Column(type: Types::TEXT)]
    private ?string $entity_data = null; // Données JSON de l'entité avant suppression

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: true)]
    private ?User $deleted_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $deleted_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deletion_reason = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 20)]
    private ?string $deletion_type = null; // 'soft', 'hard', 'gdpr_request'

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $scheduled_hard_delete = null; // Date de suppression définitive programmée

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): ?string
    {
        return $this->entity_type;
    }

    public function setEntityType(string $entity_type): static
    {
        $this->entity_type = $entity_type;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entity_id;
    }

    public function setEntityId(int $entity_id): static
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    public function getEntityData(): ?string
    {
        return $this->entity_data;
    }

    public function setEntityData(string $entity_data): static
    {
        $this->entity_data = $entity_data;
        return $this;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deleted_by;
    }

    public function setDeletedBy(?User $deleted_by): static
    {
        $this->deleted_by = $deleted_by;
        return $this;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(\DateTime $deleted_at): static
    {
        $this->deleted_at = $deleted_at;
        return $this;
    }

    public function getDeletionReason(): ?string
    {
        return $this->deletion_reason;
    }

    public function setDeletionReason(?string $deletion_reason): static
    {
        $this->deletion_reason = $deletion_reason;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(?string $ip_address): static
    {
        $this->ip_address = $ip_address;
        return $this;
    }

    public function getDeletionType(): ?string
    {
        return $this->deletion_type;
    }

    public function setDeletionType(string $deletion_type): static
    {
        $this->deletion_type = $deletion_type;
        return $this;
    }

    public function getScheduledHardDelete(): ?\DateTime
    {
        return $this->scheduled_hard_delete;
    }

    public function setScheduledHardDelete(?\DateTime $scheduled_hard_delete): static
    {
        $this->scheduled_hard_delete = $scheduled_hard_delete;
        return $this;
    }
}