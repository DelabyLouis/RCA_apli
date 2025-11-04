<?php

namespace App\Entity;

use App\Repository\AuditTrailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditTrailRepository::class)]
class AuditTrail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private ?string $action = null; // 'view', 'create', 'update', 'delete', 'export'

    #[ORM\Column(length: 100)]
    private ?string $entity_type = null; // Nom de la classe d'entité

    #[ORM\Column(nullable: true)]
    private ?int $entity_id = null; // ID de l'entité concernée

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null; // Détails de l'action (JSON)

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $created_at = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $user_agent = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $session_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route_name = null;

    #[ORM\Column(length: 20)]
    private ?string $severity = null; // 'info', 'warning', 'critical'

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
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

    public function setEntityId(?int $entity_id): static
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;
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

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(?string $user_agent): static
    {
        $this->user_agent = $user_agent;
        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->session_id;
    }

    public function setSessionId(?string $session_id): static
    {
        $this->session_id = $session_id;
        return $this;
    }

    public function getRouteName(): ?string
    {
        return $this->route_name;
    }

    public function setRouteName(?string $route_name): static
    {
        $this->route_name = $route_name;
        return $this;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;
        return $this;
    }
}