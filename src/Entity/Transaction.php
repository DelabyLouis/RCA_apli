<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\UniqueConstraint(name: "unique_numero_ordre_exercice", columns: ["numero_ordre", "id_exercice"])]
#[UniqueEntity(fields: ['libelle'], message: 'Ce libellé existe déjà pour une autre transaction')]
#[Assert\Callback(callback: 'validatePersonneOrEntreprise')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_transaction')]
    private ?int $id_transaction = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le libellé ne peut pas être vide')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le libellé doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $numero_ordre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire')]
    #[Assert\Type(\DateTime::class)]
    private ?\DateTime $date_transaction = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\NotEqualTo(value: 0, message: 'Le montant ne peut pas être égal à zéro')]
    #[Assert\Regex(
        pattern: '/^-?\d{1,13}(\.\d{1,2})?$/',
        message: 'Le montant doit être un nombre valide avec au maximum 2 décimales'
    )]
    private ?string $montant = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'id_exercice', referencedColumnName: 'id_exercice', nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'id_type', referencedColumnName: 'id_type', nullable: true)]
    private ?TypeTransaction $type_transaction = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'id_personne', referencedColumnName: 'id_personne', nullable: true)]
    private ?Personne $personne = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'id_entreprise', referencedColumnName: 'id_entreprise', nullable: true)]
    private ?Entreprise $entreprise = null;

    public function getIdTransaction(): ?int
    {
        return $this->id_transaction;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getNumeroOrdre(): ?int
    {
        return $this->numero_ordre;
    }

    public function setNumeroOrdre(int $numero_ordre): static
    {
        $this->numero_ordre = $numero_ordre;

        return $this;
    }

    public function getDateTransaction(): ?\DateTime
    {
        return $this->date_transaction;
    }

    public function setDateTransaction(\DateTime $date_transaction): static
    {
        $this->date_transaction = $date_transaction;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
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

    public function getTypeTransaction(): ?TypeTransaction
    {
        return $this->type_transaction;
    }

    public function setTypeTransaction(?TypeTransaction $type_transaction): static
    {
        $this->type_transaction = $type_transaction;

        return $this;
    }

    public function getPersonne(): ?Personne
    {
        return $this->personne;
    }

    public function setPersonne(?Personne $personne): static
    {
        $this->personne = $personne;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * Validation XOR : soit Personne soit Entreprise, mais pas les deux
     */
    public function validatePersonneOrEntreprise(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        // Si ni personne ni entreprise n'est définie
        if ($this->personne === null && $this->entreprise === null) {
            $context->buildViolation('Une transaction doit être liée soit à une personne soit à une entreprise.')
                ->atPath('personne')
                ->addViolation();
        }

        // Si les deux sont définies
        if ($this->personne !== null && $this->entreprise !== null) {
            $context->buildViolation('Une transaction ne peut pas être liée à la fois à une personne et à une entreprise.')
                ->atPath('personne')
                ->addViolation();
        }
    }
}