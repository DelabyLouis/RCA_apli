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
        // ========== CRÉATION DES RÔLES ==========
        $roleAdmin = new Role();
        $roleAdmin->setLibelle('ADMIN');
        $roleAdmin->setDescription('Administrateur du système avec tous les droits');
        $manager->persist($roleAdmin);

        $roleUser = new Role();
        $roleUser->setLibelle('USER');
        $roleUser->setDescription('Utilisateur standard du système');
        $manager->persist($roleUser);

        $roleComptable = new Role();
        $roleComptable->setLibelle('COMPTABLE');
        $roleComptable->setDescription('Comptable avec accès aux transactions et exercices');
        $manager->persist($roleComptable);

        $roleDirecteur = new Role();
        $roleDirecteur->setLibelle('DIRECTEUR');
        $roleDirecteur->setDescription('Directeur avec accès étendu aux rapports');
        $manager->persist($roleDirecteur);

        // ========== CRÉATION DES ENTREPRISES ==========
        $entreprise1 = new Entreprise();
        $entreprise1->setNomEntreprise('SARL TechSolutions');
        $entreprise1->setSiret('12345678901234');
        $entreprise1->setSiren('123456789');
        $entreprise1->setNumeroVoie('15');
        $entreprise1->setRue('Avenue des Champs-Élysées');
        $entreprise1->setComplementAdresse('Bâtiment A - 3ème étage');
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
        $entreprise2->setComplementAdresse('Centre d\'affaires Lyon Part-Dieu');
        $entreprise2->setVille('Lyon');
        $entreprise2->setCodePostal(69001);
        $entreprise2->setPays('France');
        $entreprise2->setTelephone(478912345);
        $entreprise2->setEmail('info@innovationhub.fr');
        $manager->persist($entreprise2);

        $entreprise3 = new Entreprise();
        $entreprise3->setNomEntreprise('EURL ConseilPlus');
        $entreprise3->setSiret('11111111111111');
        $entreprise3->setSiren('111111111');
        $entreprise3->setNumeroVoie('8');
        $entreprise3->setRue('Place Bellecour');
        $entreprise3->setVille('Lyon');
        $entreprise3->setCodePostal(69002);
        $entreprise3->setPays('France');
        $entreprise3->setTelephone(478555666);
        $entreprise3->setEmail('contact@conseilplus.fr');
        $manager->persist($entreprise3);

        // ========== CRÉATION DES PERSONNES ==========
        $personne1 = new Personne();
        $personne1->setNom('Dupont');
        $personne1->setPrenom('Jean');
        $personne1->setCivilite('M.');
        $personne1->setNumeroVoie('10');
        $personne1->setRue('Rue de la Paix');
        $personne1->setComplementAdresse('Appartement 3B');
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
        $personne3->setNom('Administrateur');
        $personne3->setPrenom('Système');
        $personne3->setCivilite('M.');
        $personne3->setNumeroVoie('1');
        $personne3->setRue('Rue de l\'Administration');
        $personne3->setVille('Paris');
        $personne3->setCodePostal(75000);
        $personne3->setPays('France');
        $personne3->setTelephone(100000000);
        $personne3->setEmail('admin@rca-appli.fr');
        $manager->persist($personne3);

        $personne4 = new Personne();
        $personne4->setNom('Durand');
        $personne4->setPrenom('Pierre');
        $personne4->setCivilite('M.');
        $personne4->setNumeroVoie('33');
        $personne4->setRue('Avenue de la Liberté');
        $personne4->setVille('Marseille');
        $personne4->setCodePostal(13001);
        $personne4->setPays('France');
        $personne4->setTelephone(491234567);
        $personne4->setEmail('pierre.durand@email.fr');
        $personne4->addEntreprise($entreprise3);
        $manager->persist($personne4);

        $personne5 = new Personne();
        $personne5->setNom('Leclerc');
        $personne5->setPrenom('Sophie');
        $personne5->setCivilite('Mme');
        $personne5->setNumeroVoie('17');
        $personne5->setRue('Rue du Commerce');
        $personne5->setVille('Toulouse');
        $personne5->setCodePostal(31000);
        $personne5->setPays('France');
        $personne5->setTelephone(561987654);
        $personne5->setEmail('sophie.leclerc@email.fr');
        $manager->persist($personne5);

        // ========== CRÉATION DES UTILISATEURS ==========
        // Tous les mots de passe sont "azerty"
        
        // Utilisateur Admin
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, 'azerty'));
        $userAdmin->setPersonne($personne3);
        $userAdmin->addRole($roleAdmin);
        $userAdmin->addRole($roleUser);
        $manager->persist($userAdmin);

        // Utilisateur Jean Dupont (Comptable)
        $userJean = new User();
        $userJean->setUsername('jean.dupont');
        $userJean->setPassword($this->passwordHasher->hashPassword($userJean, 'azerty'));
        $userJean->setPersonne($personne1);
        $userJean->addRole($roleUser);
        $userJean->addRole($roleComptable);
        $manager->persist($userJean);

        // Utilisateur Marie Martin (Directrice)
        $userMarie = new User();
        $userMarie->setUsername('marie.martin');
        $userMarie->setPassword($this->passwordHasher->hashPassword($userMarie, 'azerty'));
        $userMarie->setPersonne($personne2);
        $userMarie->addRole($roleUser);
        $userMarie->addRole($roleDirecteur);
        $manager->persist($userMarie);

        // Utilisateur Pierre Durand (Utilisateur simple)
        $userPierre = new User();
        $userPierre->setUsername('pierre.durand');
        $userPierre->setPassword($this->passwordHasher->hashPassword($userPierre, 'azerty'));
        $userPierre->setPersonne($personne4);
        $userPierre->addRole($roleUser);
        $manager->persist($userPierre);

        // Utilisateur Sophie Leclerc (Comptable et Directrice)
        $userSophie = new User();
        $userSophie->setUsername('sophie.leclerc');
        $userSophie->setPassword($this->passwordHasher->hashPassword($userSophie, 'azerty'));
        $userSophie->setPersonne($personne5);
        $userSophie->addRole($roleUser);
        $userSophie->addRole($roleComptable);
        $userSophie->addRole($roleDirecteur);
        $manager->persist($userSophie);

        // ========== CRÉATION DES EXERCICES ==========
        $exercice2023 = new Exercice();
        $exercice2023->setLibelle('Exercice Comptable 2023');
        $exercice2023->setNumeroOrdre(1);
        $exercice2023->setDateDebut(new \DateTime('2023-01-01'));
        $exercice2023->setDateFin(new \DateTime('2023-12-31'));
        $exercice2023->setClos(true);
        $manager->persist($exercice2023);

        $exercice2024 = new Exercice();
        $exercice2024->setLibelle('Exercice Comptable 2024');
        $exercice2024->setNumeroOrdre(2);
        $exercice2024->setDateDebut(new \DateTime('2024-01-01'));
        $exercice2024->setDateFin(new \DateTime('2024-12-31'));
        $exercice2024->setClos(false);
        $manager->persist($exercice2024);

        $exercice2025 = new Exercice();
        $exercice2025->setLibelle('Exercice Comptable 2025');
        $exercice2025->setNumeroOrdre(3);
        $exercice2025->setDateDebut(new \DateTime('2025-01-01'));
        $exercice2025->setDateFin(new \DateTime('2025-12-31'));
        $exercice2025->setClos(false);
        $manager->persist($exercice2025);

        // ========== CRÉATION DES TYPES DE TRANSACTION ==========
        $typeVente = new TypeTransaction();
        $typeVente->setLibelle('Vente');
        $typeVente->setDescription('Vente de produits ou services');
        $manager->persist($typeVente);

        $typeAchat = new TypeTransaction();
        $typeAchat->setLibelle('Achat');
        $typeAchat->setDescription('Achat de marchandises, fournitures ou services');
        $manager->persist($typeAchat);

        $typeBanque = new TypeTransaction();
        $typeBanque->setLibelle('Banque');
        $typeBanque->setDescription('Opérations bancaires (virements, prélèvements, etc.)');
        $manager->persist($typeBanque);

        $typeSalaire = new TypeTransaction();
        $typeSalaire->setLibelle('Salaire');
        $typeSalaire->setDescription('Paiement des salaires et charges sociales');
        $manager->persist($typeSalaire);

        $typeLocation = new TypeTransaction();
        $typeLocation->setLibelle('Location');
        $typeLocation->setDescription('Loyers et charges locatives');
        $manager->persist($typeLocation);

        // ========== CRÉATION DES TRANSACTIONS ==========
        
        // Transactions pour l'exercice 2024
        $transaction1 = new Transaction();
        $transaction1->setLibelle('Vente matériel informatique TechSolutions');
        $transaction1->setNumeroOrdre(1);
        $transaction1->setExercice($exercice2024);
        $transaction1->setTypeTransaction($typeVente);
        $transaction1->setEntreprise($entreprise1);
        $transaction1->setDateTransaction(new \DateTime('2024-01-15'));
        $transaction1->setMontant(15000.00);
        $manager->persist($transaction1);

        $transaction2 = new Transaction();
        $transaction2->setLibelle('Achat fournitures bureau');
        $transaction2->setNumeroOrdre(2);
        $transaction2->setExercice($exercice2024);
        $transaction2->setTypeTransaction($typeAchat);
        $transaction2->setPersonne($personne1);
        $transaction2->setDateTransaction(new \DateTime('2024-01-20'));
        $transaction2->setMontant(-850.50);
        $manager->persist($transaction2);

        $transaction3 = new Transaction();
        $transaction3->setLibelle('Salaire Jean Dupont - Janvier 2024');
        $transaction3->setNumeroOrdre(3);
        $transaction3->setExercice($exercice2024);
        $transaction3->setTypeTransaction($typeSalaire);
        $transaction3->setPersonne($personne1);
        $transaction3->setDateTransaction(new \DateTime('2024-01-31'));
        $transaction3->setMontant(-3500.00);
        $manager->persist($transaction3);

        $transaction4 = new Transaction();
        $transaction4->setLibelle('Prestation conseil Innovation Hub');
        $transaction4->setNumeroOrdre(4);
        $transaction4->setExercice($exercice2024);
        $transaction4->setTypeTransaction($typeVente);
        $transaction4->setEntreprise($entreprise2);
        $transaction4->setDateTransaction(new \DateTime('2024-02-10'));
        $transaction4->setMontant(8500.00);
        $manager->persist($transaction4);

        $transaction5 = new Transaction();
        $transaction5->setLibelle('Loyer bureau - Février 2024');
        $transaction5->setNumeroOrdre(5);
        $transaction5->setExercice($exercice2024);
        $transaction5->setTypeTransaction($typeLocation);
        $transaction5->setDateTransaction(new \DateTime('2024-02-01'));
        $transaction5->setMontant(-2200.00);
        $manager->persist($transaction5);

        $transaction6 = new Transaction();
        $transaction6->setLibelle('Virement bancaire reçu');
        $transaction6->setNumeroOrdre(6);
        $transaction6->setExercice($exercice2024);
        $transaction6->setTypeTransaction($typeBanque);
        $transaction6->setDateTransaction(new \DateTime('2024-02-15'));
        $transaction6->setMontant(12000.00);
        $manager->persist($transaction6);

        // Transactions pour l'exercice 2025
        $transaction7 = new Transaction();
        $transaction7->setLibelle('Vente logiciel sur mesure');
        $transaction7->setNumeroOrdre(1);
        $transaction7->setExercice($exercice2025);
        $transaction7->setTypeTransaction($typeVente);
        $transaction7->setEntreprise($entreprise3);
        $transaction7->setDateTransaction(new \DateTime('2025-01-10'));
        $transaction7->setMontant(25000.00);
        $manager->persist($transaction7);

        $transaction8 = new Transaction();
        $transaction8->setLibelle('Achat serveur professionnel');
        $transaction8->setNumeroOrdre(2);
        $transaction8->setExercice($exercice2025);
        $transaction8->setTypeTransaction($typeAchat);
        $transaction8->setDateTransaction(new \DateTime('2025-01-25'));
        $transaction8->setMontant(-4500.00);
        $manager->persist($transaction8);

        $transaction9 = new Transaction();
        $transaction9->setLibelle('Salaire Marie Martin - Janvier 2025');
        $transaction9->setNumeroOrdre(3);
        $transaction9->setExercice($exercice2025);
        $transaction9->setTypeTransaction($typeSalaire);
        $transaction9->setPersonne($personne2);
        $transaction9->setDateTransaction(new \DateTime('2025-01-31'));
        $transaction9->setMontant(-4200.00);
        $manager->persist($transaction9);

        $transaction10 = new Transaction();
        $transaction10->setLibelle('Formation développement équipe');
        $transaction10->setNumeroOrdre(4);
        $transaction10->setExercice($exercice2025);
        $transaction10->setTypeTransaction($typeAchat);
        $transaction10->setDateTransaction(new \DateTime('2025-02-05'));
        $transaction10->setMontant(-1800.00);
        $manager->persist($transaction10);

        $manager->flush();
    }
}