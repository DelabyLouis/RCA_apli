<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[UniqueEntity(fields: ['siret'], message: 'Ce numéro SIRET existe déjà')]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_entreprise')]
    private ?int $id_entreprise = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'entreprise ne peut pas être vide')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom de l\'entreprise doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom de l\'entreprise ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $nom_entreprise = null;

    #[ORM\Column(length: 14, nullable: true, unique: true)]
    #[Assert\Length(
        exactly: 14,
        exactMessage: 'Le SIRET doit contenir exactement {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^\d{14}$/',
        message: 'Le SIRET doit contenir uniquement des chiffres'
    )]
    private ?string $siret = null;

    #[ORM\Column(length: 9, nullable: true)]
    private ?string $siren = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $numero_voie = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $rue = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $complement_adresse = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $code_postal = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'France'])]
    private ?string $pays = 'France';

    #[ORM\Column(nullable: true)]
    private ?int $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    /**
     * @var Collection<int, Personne>
     */
    #[ORM\ManyToMany(targetEntity: Personne::class, mappedBy: 'entreprise')]
    private Collection $personnes;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'entreprise')]
    private Collection $transactions;

    public function __construct()
    {
        $this->personnes = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getIdEntreprise(): ?int
    {
        return $this->id_entreprise;
    }

    public function getNomEntreprise(): ?string
    {
        return $this->nom_entreprise;
    }

    public function setNomEntreprise(string $nom_entreprise): static
    {
        $this->nom_entreprise = $nom_entreprise;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getNumeroVoie(): ?string
    {
        return $this->numero_voie;
    }

    public function setNumeroVoie(?string $numero_voie): static
    {
        $this->numero_voie = $numero_voie;

        return $this;
    }

    public function getRue(): ?string
    {
        return $this->rue;
    }

    public function setRue(?string $rue): static
    {
        $this->rue = $rue;

        return $this;
    }

    public function getComplementAdresse(): ?string
    {
        return $this->complement_adresse;
    }

    public function setComplementAdresse(?string $complement_adresse): static
    {
        $this->complement_adresse = $complement_adresse;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    public function setCodePostal(?string $code_postal): static
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getTelephone(): ?int
    {
        return $this->telephone;
    }

    public function setTelephone(?int $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Personne>
     */
    public function getPersonnes(): Collection
    {
        return $this->personnes;
    }

    public function addPersonne(Personne $personne): static
    {
        if (!$this->personnes->contains($personne)) {
            $this->personnes->add($personne);
            $personne->addEntreprise($this);
        }

        return $this;
    }

    public function removePersonne(Personne $personne): static
    {
        if ($this->personnes->removeElement($personne)) {
            $personne->removeEntreprise($this);
        }

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
            $transaction->setEntreprise($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getEntreprise() === $this) {
                $transaction->setEntreprise(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom_entreprise ?? '';
    }
}
