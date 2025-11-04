<?php

namespace App\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait pour implémenter le soft delete sur les entités
 */
trait SoftDeletableTrait
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $deleted_at = null;

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTime $deleted_at): self
    {
        $this->deleted_at = $deleted_at;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function delete(): self
    {
        $this->deleted_at = new \DateTime();
        return $this;
    }

    public function restore(): self
    {
        $this->deleted_at = null;
        return $this;
    }
}