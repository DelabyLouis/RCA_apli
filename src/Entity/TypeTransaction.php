<?php

namespace App\Entity;

use App\Repository\TypeTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: TypeTransactionRepository::class)]
#[UniqueEntity(fields: ['libelle'], message: 'Ce type de transaction existe déjà')]
class TypeTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_type')]
    private ?int $id_type = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le libellé du type de transaction ne peut pas être vide')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le libellé doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, nullable: false, options: ['default' => 'both'])]
    #[Assert\Choice(
        choices: ['debit', 'credit', 'both'],
        message: 'Le type de montant autorisé doit être "debit", "credit" ou "both"'
    )]
    private string $type_montant_autorise = 'both';

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'type_transaction')]
    private Collection $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getIdType(): ?int
    {
        return $this->id_type;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTypeMontantAutorise(): string
    {
        return $this->type_montant_autorise;
    }

    public function setTypeMontantAutorise(string $type_montant_autorise): static
    {
        $this->type_montant_autorise = $type_montant_autorise;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setTypeTransaction($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getTypeTransaction() === $this) {
                $transaction->setTypeTransaction(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}
