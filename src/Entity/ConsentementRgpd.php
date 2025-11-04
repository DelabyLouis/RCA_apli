<?php

namespace App\Entity;

use App\Repository\ConsentementRgpdRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsentementRgpdRepository::class)]
class ConsentementRgpd
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $type_consentement = null; // 'privacy_policy', 'communication', etc.

    #[ORM\Column]
    private ?bool $accepte = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $date_consentement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contexte = null; // Contexte du consentement (inscription, modification profil, etc.)

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $adresse_ip = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $date_retrait = null;

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

    public function getTypeConsentement(): ?string
    {
        return $this->type_consentement;
    }

    public function setTypeConsentement(string $type_consentement): static
    {
        $this->type_consentement = $type_consentement;

        return $this;
    }

    public function isAccepte(): ?bool
    {
        return $this->accepte;
    }

    public function setAccepte(bool $accepte): static
    {
        $this->accepte = $accepte;

        return $this;
    }

    public function getDateConsentement(): ?\DateTime
    {
        return $this->date_consentement;
    }

    public function setDateConsentement(\DateTime $date_consentement): static
    {
        $this->date_consentement = $date_consentement;

        return $this;
    }

    public function getContexte(): ?string
    {
        return $this->contexte;
    }

    public function setContexte(?string $contexte): static
    {
        $this->contexte = $contexte;

        return $this;
    }

    public function getAdresseIp(): ?string
    {
        return $this->adresse_ip;
    }

    public function setAdresseIp(?string $adresse_ip): static
    {
        $this->adresse_ip = $adresse_ip;

        return $this;
    }

    public function getDateRetrait(): ?\DateTime
    {
        return $this->date_retrait;
    }

    public function setDateRetrait(?\DateTime $date_retrait): static
    {
        $this->date_retrait = $date_retrait;

        return $this;
    }

    /**
     * Vérifie si le consentement est actuellement valide (accepté et pas retiré)
     */
    public function isValide(): bool
    {
        return $this->accepte && $this->date_retrait === null;
    }
}