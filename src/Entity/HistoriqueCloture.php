<?php

namespace App\Entity;

use App\Repository\HistoriqueCloturRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueCloturRepository::class)]
class HistoriqueCloture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_historique')]
    private ?int $id_historique = null;

    #[ORM\ManyToOne(targetEntity: Exercice::class, inversedBy: 'historiquesCloture')]
    #[ORM\JoinColumn(name: 'id_exercice', referencedColumnName: 'id_exercice', nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $date_action = null;

    #[ORM\Column(length: 50)]
    private ?string $type_action = null; // 'CLOTURE' ou 'DECLOTURE'

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    public function getIdHistorique(): ?int
    {
        return $this->id_historique;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(?Exercice $exercice): static
    {
        $this->exercice = $exercice;

        return $this;
    }

    public function getDateAction(): ?\DateTime
    {
        return $this->date_action;
    }

    public function setDateAction(\DateTime $date_action): static
    {
        $this->date_action = $date_action;

        return $this;
    }

    public function getTypeAction(): ?string
    {
        return $this->type_action;
    }

    public function setTypeAction(string $type_action): static
    {
        $this->type_action = $type_action;

        return $this;
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function __toString(): string
    {
        return $this->type_action . ' - ' . ($this->date_action ? $this->date_action->format('Y-m-d H:i') : '');
    }
}