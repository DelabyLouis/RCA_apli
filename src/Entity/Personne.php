<?php

namespace App\Entity;

use App\Repository\PersonneRepository;
use App\Trait\SoftDeletableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PersonneRepository::class)]
class Personne
{
    use SoftDeletableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_personne')]
    private ?int $id_personne = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $civilite = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $numero_voie = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $rue = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $complement_adresse = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(nullable: true)]
    private ?int $code_postal = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'France'])]
    private ?string $pays = 'France';

    #[ORM\Column(nullable: true)]
    private ?int $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    /**
     * @var Collection<int, Entreprise>
     */
    #[ORM\ManyToMany(targetEntity: Entreprise::class, inversedBy: 'personnes')]
    #[ORM\JoinTable(name: 'personne_entreprise')]
    #[ORM\JoinColumn(name: 'id_personne', referencedColumnName: 'id_personne')]
    #[ORM\InverseJoinColumn(name: 'id_entreprise', referencedColumnName: 'id_entreprise')]
    private Collection $entreprise;

    #[ORM\OneToOne(targetEntity: User::class, mappedBy: 'personne')]
    private ?User $user = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'personne')]
    private Collection $transactions;

    public function __construct()
    {
        $this->entreprise = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getIdPersonne(): ?int
    {
        return $this->id_personne;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getCivilite(): ?string
    {
        return $this->civilite;
    }

    public function setCivilite(?string $civilite): static
    {
        $this->civilite = $civilite;

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

    public function getCodePostal(): ?int
    {
        return $this->code_postal;
    }

    public function setCodePostal(?int $code_postal): static
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
     * @return Collection<int, Entreprise>
     */
    public function getEntreprise(): Collection
    {
        return $this->entreprise;
    }

    public function addEntreprise(Entreprise $entreprise): static
    {
        if (!$this->entreprise->contains($entreprise)) {
            $this->entreprise->add($entreprise);
        }

        return $this;
    }

    public function removeEntreprise(Entreprise $entreprise): static
    {
        $this->entreprise->removeElement($entreprise);

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
            $transaction->setPersonne($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getPersonne() === $this) {
                $transaction->setPersonne(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }
}
