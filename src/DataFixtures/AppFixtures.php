<?php

namespace App\DataFixtures;

use App\Entity\Entreprise;
use App\Entity\Role;
use App\Entity\Personne;
use App\Entity\User;
use App\Entity\Exercice;
use App\Entity\TypeTransaction;
use App\Entity\ModeDePaiement;
use App\Entity\Transaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // ========== MODES DE PAIEMENT ==========
        $modeFacture = new ModeDePaiement();
        $modeFacture->setLibelle('Facture');
        $manager->persist($modeFacture);

        $modeCheque = new ModeDePaiement();
        $modeCheque->setLibelle('Chèque');
        $manager->persist($modeCheque);

        $modeVirement = new ModeDePaiement();
        $modeVirement->setLibelle('Virement');
        $manager->persist($modeVirement);

        $modeLivret = new ModeDePaiement();
        $modeLivret->setLibelle('Livret');
        $manager->persist($modeLivret);

        // ========== RÔLES ==========
        $roleAdmin = new Role();
        $roleAdmin->setLibelle('ADMIN');
        $roleAdmin->setDescription('Administrateur');
        $manager->persist($roleAdmin);

        // ========== PERSONNES ==========
        $personne = new Personne();
        $personne->setNom('Dupont');
        $personne->setPrenom('Jean');
        $personne->setRue('1 rue Test');
        $personne->setVille('Paris');
        $personne->setCodePostal(75001);
        $personne->setTelephone(null);
        $personne->setEmail('jean@test.fr');
        $manager->persist($personne);

        // ========== ENTREPRISES ==========
        $entreprise = new Entreprise();
        $entreprise->setNomEntreprise('TechCorp');
        $entreprise->setRue('123 Tech Avenue');
        $entreprise->setVille('Paris');
        $entreprise->setCodePostal(75001);
        $entreprise->setTelephone(null);
        $entreprise->setEmail('contact@techcorp.fr');
        $manager->persist($entreprise);

        $entrepriseLivret = new Entreprise();
        $entrepriseLivret->setNomEntreprise('Livret');
        $entrepriseLivret->setRue('Compte épargne interne');
        $entrepriseLivret->setVille('Interne');
        $entrepriseLivret->setCodePostal(00000);
        $entrepriseLivret->setTelephone(null);
        $entrepriseLivret->setEmail('');
        $manager->persist($entrepriseLivret);

        // ========== UTILISATEUR ==========
        $user = new User();
        $user->setUsername('admin');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin'));
        $user->setPersonne($personne);
        $user->addRole($roleAdmin);
        $manager->persist($user);

        // ========== EXERCICES ==========
        $exercice2025 = new Exercice();
        $exercice2025->setLibelle('Exercice Comptable 2025');
        $exercice2025->setDateDebut(new \DateTime('2025-01-01'));
        $exercice2025->setDateFin(new \DateTime('2025-12-31'));
        $exercice2025->setNumeroOrdre(1);
        $exercice2025->setClos(false);
        $manager->persist($exercice2025);

        // ========== TYPES DE TRANSACTIONS ==========
        $typeVente = new TypeTransaction();
        $typeVente->setLibelle('Vente');
        $manager->persist($typeVente);

        $typeLivret = new TypeTransaction();
        $typeLivret->setLibelle('Livret');
        $manager->persist($typeLivret);

        // Flush pour avoir les IDs
        $manager->flush();

        // ========== TRANSACTIONS ==========
        $transaction1 = new Transaction();
        $transaction1->setLibelle('N°896454856');
        $transaction1->setNumeroOrdre(1);
        $transaction1->setDateTransaction(new \DateTime('2025-02-15'));
        $transaction1->setMontant('2500.00');
        $transaction1->setExercice($exercice2025);
        $transaction1->setTypeTransaction($typeVente);
        $transaction1->setEntreprise($entreprise);
        $transaction1->setModeDePaiement($modeFacture);
        $transaction1->setTypeCompte('compte_courant');
        $manager->persist($transaction1);

        $transactionLivret = new Transaction();
        $transactionLivret->setLibelle('DepotLivret 24-10-25-12h00');
        $transactionLivret->setNumeroOrdre(2);
        $transactionLivret->setDateTransaction(new \DateTime('2025-10-24'));
        $transactionLivret->setMontant('1500.00');
        $transactionLivret->setExercice($exercice2025);
        $transactionLivret->setTypeTransaction($typeLivret);
        $transactionLivret->setEntreprise($entrepriseLivret);
        $transactionLivret->setModeDePaiement($modeLivret);
        $transactionLivret->setTypeCompte('livret');
        $manager->persist($transactionLivret);

        $manager->flush();
    }
}
