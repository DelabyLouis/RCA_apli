<?php

namespace App\Entity;

use App\Repository\ExerciceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ExerciceRepository::class)]
#[UniqueEntity(fields: ['libelle'], message: 'Ce libellé d\'exercice existe déjà')]
class Exercice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_exercice')]
    private ?int $id_exercice = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le libellé de l\'exercice ne peut pas être vide')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le libellé doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: 'Le numéro d\'ordre est obligatoire')]
    #[Assert\Positive(message: 'Le numéro d\'ordre doit être positif')]
    private ?int $numero_ordre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire')]
    #[Assert\Type(\DateTime::class)]
    private ?\DateTime $date_debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(\DateTime::class)]
    private ?\DateTime $date_fin = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $clos = false;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'exercice')]
    private Collection $transactions;

    /**
     * @var Collection<int, HistoriqueCloture>
     */
    #[ORM\OneToMany(targetEntity: HistoriqueCloture::class, mappedBy: 'exercice', cascade: ['persist', 'remove'])]
    private Collection $historiquesCloture;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->historiquesCloture = new ArrayCollection();
    }

    public function getIdExercice(): ?int
    {
        return $this->id_exercice;
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

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

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
            $transaction->setExercice($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getExercice() === $this) {
                $transaction->setExercice(null);
            }
        }

        return $this;
    }

    public function isClos(): ?bool
    {
        return $this->clos;
    }

    public function setClos(bool $clos): static
    {
        $this->clos = $clos;

        return $this;
    }

    /**
     * @return Collection<int, HistoriqueCloture>
     */
    public function getHistoriquesCloture(): Collection
    {
        return $this->historiquesCloture;
    }

    public function addHistoriqueCloture(HistoriqueCloture $historiqueCloture): static
    {
        if (!$this->historiquesCloture->contains($historiqueCloture)) {
            $this->historiquesCloture->add($historiqueCloture);
            $historiqueCloture->setExercice($this);
        }

        return $this;
    }

    public function removeHistoriqueCloture(HistoriqueCloture $historiqueCloture): static
    {
        if ($this->historiquesCloture->removeElement($historiqueCloture)) {
            // set the owning side to null (unless already changed)
            if ($historiqueCloture->getExercice() === $this) {
                $historiqueCloture->setExercice(null);
            }
        }

        return $this;
    }

    /**
     * Retourne true si l'exercice peut être modifié (pas clos)
     */
    public function isModifiable(): bool
    {
        return !$this->clos;
    }

    /**
     * Retourne la dernière action de clôture/déclôture
     */
    public function getDerniereActionCloture(): ?HistoriqueCloture
    {
        if ($this->historiquesCloture->isEmpty()) {
            return null;
        }

        // Trier par date décroissante et retourner le premier
        $historiques = $this->historiquesCloture->toArray();
        usort($historiques, function($a, $b) {
            return $b->getDateAction() <=> $a->getDateAction();
        });

        return $historiques[0] ?? null;
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

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}
