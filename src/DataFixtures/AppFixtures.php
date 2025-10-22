<?php

namespace App\DataFixtures;

use App\Entity\Entreprise;
use App\Entity\Role;
use App\Entity\Personne;
use App\Entity\User;
use App\Entity\Exercice;
use App\Entity\TypeTransaction;
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
        // Création des rôles
        $roleAdmin = new Role();
        $roleAdmin->setLibelle('ADMIN');
        $roleAdmin->setDescription('Administrateur du système');
        $manager->persist($roleAdmin);

        $roleUser = new Role();
        $roleUser->setLibelle('USER');
        $roleUser->setDescription('Utilisateur standard');
        $manager->persist($roleUser);

        $roleComptable = new Role();
        $roleComptable->setLibelle('COMPTABLE');
        $roleComptable->setDescription('Comptable de l\'entreprise');
        $manager->persist($roleComptable);

        // Création d'entreprises
        $entreprise1 = new Entreprise();
        $entreprise1->setNomEntreprise('SARL TechSolutions');
        $entreprise1->setSiret('12345678901234');
        $entreprise1->setSiren('123456789');
        $entreprise1->setNumeroVoie('15');
        $entreprise1->setRue('Avenue des Champs-Élysées');
        $entreprise1->setVille('Paris');
        $entreprise1->setCodePostal(75008);
        $entreprise1->setPays('France');
        $entreprise1->setTelephone(145236789);
        $entreprise1->setEmail('contact@techsolutions.fr');
        $manager->persist($entreprise1);

        $entreprise2 = new Entreprise();
        $entreprise2->setNomEntreprise('SAS Innovation Hub');
        $entreprise2->setSiret('98765432109876');
        $entreprise2->setSiren('987654321');
        $entreprise2->setNumeroVoie('42');
        $entreprise2->setRue('Rue de la République');
        $entreprise2->setVille('Lyon');
        $entreprise2->setCodePostal(69001);
        $entreprise2->setPays('France');
        $entreprise2->setTelephone(478912345);
        $entreprise2->setEmail('info@innovationhub.fr');
        $manager->persist($entreprise2);

        // Création de personnes
        $personne1 = new Personne();
        $personne1->setNom('Dupont');
        $personne1->setPrenom('Jean');
        $personne1->setCivilite('M.');
        $personne1->setNumeroVoie('10');
        $personne1->setRue('Rue de la Paix');
        $personne1->setVille('Paris');
        $personne1->setCodePostal(75001);
        $personne1->setPays('France');
        $personne1->setTelephone(123456789);
        $personne1->setEmail('jean.dupont@email.fr');
        $personne1->addEntreprise($entreprise1);
        $manager->persist($personne1);

        $personne2 = new Personne();
        $personne2->setNom('Martin');
        $personne2->setPrenom('Marie');
        $personne2->setCivilite('Mme');
        $personne2->setNumeroVoie('25');
        $personne2->setRue('Boulevard Saint-Germain');
        $personne2->setVille('Lyon');
        $personne2->setCodePostal(69002);
        $personne2->setPays('France');
        $personne2->setTelephone(987654321);
        $personne2->setEmail('marie.martin@email.fr');
        $personne2->addEntreprise($entreprise2);
        $manager->persist($personne2);

        $personne3 = new Personne();
        $personne3->setNom('Admin');
        $personne3->setPrenom('Super');
        $personne3->setCivilite('M.');
        $personne3->setNumeroVoie('1');
        $personne3->setRue('Rue de l\'Administration');
        $personne3->setVille('Paris');
        $personne3->setCodePostal(75000);
        $personne3->setPays('France');
        $personne3->setTelephone(100000000);
        $personne3->setEmail('admin@system.fr');
        $manager->persist($personne3);

        // Création des utilisateurs
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, 'admin123'));
        $userAdmin->setPersonne($personne3);
        $userAdmin->addRole($roleAdmin);
        $manager->persist($userAdmin);

        $user1 = new User();
        $user1->setUsername('jean.dupont');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));
        $user1->setPersonne($personne1);
        $user1->addRole($roleComptable);
        $user1->addRole($roleUser); // Exemple : ajout de plusieurs rôles
        $manager->persist($user1);

        $user2 = new User();
        $user2->setUsername('marie.martin');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password456'));
        $user2->setPersonne($personne2);
        $user2->addRole($roleUser);
        $manager->persist($user2);

        // Création des exercices
        $exercice2024 = new Exercice();
        $exercice2024->setLibelle('Exercice 2024');
        $exercice2024->setNumeroOrdre(1);
        $exercice2024->setDateDebut(new \DateTime('2024-01-01'));
        $exercice2024->setDateFin(new \DateTime('2024-12-31'));
        $manager->persist($exercice2024);

        $exercice2023 = new Exercice();
        $exercice2023->setLibelle('Exercice 2023');
        $exercice2023->setNumeroOrdre(2);
        $exercice2023->setDateDebut(new \DateTime('2023-01-01'));
        $exercice2023->setDateFin(new \DateTime('2023-12-31'));
        $manager->persist($exercice2023);

        // Création des types de transactions
        $typeVente = new TypeTransaction();
        $typeVente->setLibelle('Vente');
        $typeVente->setDescription('Transaction de vente de produits ou services');
        $manager->persist($typeVente);

        $typeAchat = new TypeTransaction();
        $typeAchat->setLibelle('Achat');
        $typeAchat->setDescription('Transaction d\'achat de matériel ou services');
        $manager->persist($typeAchat);

        $typeSalaire = new TypeTransaction();
        $typeSalaire->setLibelle('Salaire');
        $typeSalaire->setDescription('Paiement des salaires');
        $manager->persist($typeSalaire);

        // Sauvegarde intermédiaire pour avoir les IDs
        $manager->flush();

        // Création des transactions
        $transaction1 = new Transaction();
        $transaction1->setLibelle('Vente services informatiques');
        $transaction1->setNumeroOrdre(1);
        $transaction1->setMontant('5000.00');
        $transaction1->setDateTransaction(new \DateTime('2024-03-15'));
        $transaction1->setTypeTransaction($typeVente);
        $transaction1->setExercice($exercice2024);
        $transaction1->setEntreprise($entreprise1); // Transaction avec une entreprise
        $manager->persist($transaction1);

        $transaction2 = new Transaction();
        $transaction2->setLibelle('Achat matériel informatique');
        $transaction2->setNumeroOrdre(2);
        $transaction2->setMontant('-3000.00');
        $transaction2->setDateTransaction(new \DateTime('2024-02-10'));
        $transaction2->setTypeTransaction($typeAchat);
        $transaction2->setExercice($exercice2024);
        $transaction2->setEntreprise($entreprise2); // Transaction avec une entreprise
        $manager->persist($transaction2);

        $transaction3 = new Transaction();
        $transaction3->setLibelle('Paiement salaires mars');
        $transaction3->setNumeroOrdre(3);
        $transaction3->setMontant('-8000.00');
        $transaction3->setDateTransaction(new \DateTime('2024-03-31'));
        $transaction3->setTypeTransaction($typeSalaire);
        $transaction3->setExercice($exercice2024);
        $transaction3->setPersonne($personne1); // Transaction avec une personne
        $manager->persist($transaction3);

        $transaction4 = new Transaction();
        $transaction4->setLibelle('Formation équipe développement');
        $transaction4->setNumeroOrdre(4);
        $transaction4->setMontant('-1500.00');
        $transaction4->setDateTransaction(new \DateTime('2024-01-20'));
        $transaction4->setTypeTransaction($typeAchat);
        $transaction4->setExercice($exercice2024);
        $transaction4->setPersonne($personne2); // Transaction avec une personne
        $manager->persist($transaction4);

        // Sauvegarde finale
        $manager->flush();
    }
}