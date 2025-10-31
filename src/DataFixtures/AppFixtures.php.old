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
        $modes = [];
        
        $modeFacture = new ModeDePaiement();
        $modeFacture->setLibelle('Facture');
        $manager->persist($modeFacture);
        $modes['facture'] = $modeFacture;

        $modeCheque = new ModeDePaiement();
        $modeCheque->setLibelle('Chèque');
        $manager->persist($modeCheque);
        $modes['cheque'] = $modeCheque;

        $modeVirement = new ModeDePaiement();
        $modeVirement->setLibelle('Virement');
        $manager->persist($modeVirement);
        $modes['virement'] = $modeVirement;

        $modeCB = new ModeDePaiement();
        $modeCB->setLibelle('Carte Bancaire');
        $manager->persist($modeCB);
        $modes['cb'] = $modeCB;

        $modeEspeces = new ModeDePaiement();
        $modeEspeces->setLibelle('Espèces');
        $manager->persist($modeEspeces);
        $modes['especes'] = $modeEspeces;

        $modePrelevement = new ModeDePaiement();
        $modePrelevement->setLibelle('Prélèvement');
        $manager->persist($modePrelevement);
        $modes['prelevement'] = $modePrelevement;

        $modeLivret = new ModeDePaiement();
        $modeLivret->setLibelle('Livret');
        $manager->persist($modeLivret);
        $modes['livret'] = $modeLivret;

        $modePaypal = new ModeDePaiement();
        $modePaypal->setLibelle('PayPal');
        $manager->persist($modePaypal);
        $modes['paypal'] = $modePaypal;

        // ========== RÔLES ==========
        $roles = [];
        
        $roleAdmin = new Role();
        $roleAdmin->setLibelle('ADMIN');
        $roleAdmin->setDescription('Administrateur système - Accès complet');
        $manager->persist($roleAdmin);
        $roles['admin'] = $roleAdmin;

        $roleComptable = new Role();
        $roleComptable->setLibelle('COMPTABLE');
        $roleComptable->setDescription('Comptable - Gestion des transactions et exercices');
        $manager->persist($roleComptable);
        $roles['comptable'] = $roleComptable;

        $roleConsultant = new Role();
        $roleConsultant->setLibelle('CONSULTANT');
        $roleConsultant->setDescription('Consultant - Accès en lecture seule');
        $manager->persist($roleConsultant);
        $roles['consultant'] = $roleConsultant;

        $roleGestionnaire = new Role();
        $roleGestionnaire->setLibelle('GESTIONNAIRE');
        $roleGestionnaire->setDescription('Gestionnaire - Gestion des clients et fournisseurs');
        $manager->persist($roleGestionnaire);
        $roles['gestionnaire'] = $roleGestionnaire;

        // ========== PERSONNES ==========
        $personnes = [];
        
        // Personnes physiques - Clients
        $p1 = new Personne();
        $p1->setCivilite('M.');
        $p1->setNom('Dupont');
        $p1->setPrenom('Jean');
        $p1->setNumeroVoie('15');
        $p1->setRue('Avenue des Champs-Élysées');
        $p1->setComplementAdresse('Appartement 3B');
        $p1->setCodePostal(75008);
        $p1->setVille('Paris');
        $p1->setPays('France');
        $p1->setTelephone(145678901);
        $p1->setEmail('jean.dupont@email.fr');
        $manager->persist($p1);
        $personnes['dupont'] = $p1;

        $p2 = new Personne();
        $p2->setCivilite('Mme');
        $p2->setNom('Martin');
        $p2->setPrenom('Sophie');
        $p2->setNumeroVoie('42');
        $p2->setRue('Rue de la République');
        $p2->setCodePostal(69001);
        $p2->setVille('Lyon');
        $p2->setPays('France');
        $p2->setTelephone(478901234);
        $p2->setEmail('sophie.martin@gmail.com');
        $manager->persist($p2);
        $personnes['martin'] = $p2;

        $p3 = new Personne();
        $p3->setCivilite('M.');
        $p3->setNom('Bernard');
        $p3->setPrenom('Michel');
        $p3->setNumeroVoie('7');
        $p3->setRue('Place Bellecour');
        $p3->setCodePostal(69002);
        $p3->setVille('Lyon');
        $p3->setPays('France');
        $p3->setTelephone(478123456);
        $p3->setEmail('m.bernard@orange.fr');
        $manager->persist($p3);
        $personnes['bernard'] = $p3;

        $p4 = new Personne();
        $p4->setCivilite('Mme');
        $p4->setNom('Lefebvre');
        $p4->setPrenom('Caroline');
        $p4->setNumeroVoie('128');
        $p4->setRue('Boulevard Saint-Germain');
        $p4->setCodePostal(75006);
        $p4->setVille('Paris');
        $p4->setPays('France');
        $p4->setTelephone(142567890);
        $p4->setEmail('caroline.lefebvre@hotmail.fr');
        $manager->persist($p4);
        $personnes['lefebvre'] = $p4;

        $p5 = new Personne();
        $p5->setCivilite('M.');
        $p5->setNom('Rousseau');
        $p5->setPrenom('Pierre');
        $p5->setNumeroVoie('33');
        $p5->setRue('Cours Mirabeau');
        $p5->setCodePostal(13100);
        $p5->setVille('Aix-en-Provence');
        $p5->setPays('France');
        $p5->setTelephone(442678901);
        $p5->setEmail('pierre.rousseau@yahoo.fr');
        $manager->persist($p5);
        $personnes['rousseau'] = $p5;

        // Employés
        $admin = new Personne();
        $admin->setCivilite('M.');
        $admin->setNom('Administrateur');
        $admin->setPrenom('Admin');
        $admin->setNumeroVoie('1');
        $admin->setRue('Rue de l\'Administration');
        $admin->setCodePostal(75001);
        $admin->setVille('Paris');
        $admin->setPays('France');
        $admin->setTelephone(101010101);
        $admin->setEmail('admin@rca-apli.fr');
        $manager->persist($admin);
        $personnes['admin'] = $admin;

        $comptable = new Personne();
        $comptable->setCivilite('Mme');
        $comptable->setNom('Compte');
        $comptable->setPrenom('Marie');
        $comptable->setNumeroVoie('25');
        $comptable->setRue('Avenue de la Comptabilité');
        $comptable->setCodePostal(75002);
        $comptable->setVille('Paris');
        $comptable->setPays('France');
        $comptable->setTelephone(201020304);
        $comptable->setEmail('marie.compte@rca-apli.fr');
        $manager->persist($comptable);
        $personnes['comptable'] = $comptable;

        // ========== ENTREPRISES ==========
        $entreprises = [];
        
        $e1 = new Entreprise();
        $e1->setNomEntreprise('TechCorp Solutions');
        $e1->setSiret('12345678901234');
        $e1->setSiren('123456789');
        $e1->setNumeroVoie('123');
        $e1->setRue('Avenue de la Technologie');
        $e1->setComplementAdresse('Bâtiment A - 3ème étage');
        $e1->setCodePostal(92400);
        $e1->setVille('Courbevoie');
        $e1->setPays('France');
        $e1->setTelephone(147589632);
        $e1->setEmail('contact@techcorp-solutions.fr');
        $manager->persist($e1);
        $entreprises['techcorp'] = $e1;

        $e2 = new Entreprise();
        $e2->setNomEntreprise('Consulting & Partners');
        $e2->setSiret('98765432109876');
        $e2->setSiren('987654321');
        $e2->setNumeroVoie('456');
        $e2->setRue('Rue du Commerce');
        $e2->setCodePostal(75008);
        $e2->setVille('Paris');
        $e2->setPays('France');
        $e2->setTelephone(145678932);
        $e2->setEmail('info@consulting-partners.com');
        $manager->persist($e2);
        $entreprises['consulting'] = $e2;

        $e3 = new Entreprise();
        $e3->setNomEntreprise('Design Studio SARL');
        $e3->setSiret('11223344556677');
        $e3->setSiren('112233445');
        $e3->setNumeroVoie('789');
        $e3->setRue('Boulevard de la Créativité');
        $e3->setCodePostal(69003);
        $e3->setVille('Lyon');
        $e3->setPays('France');
        $e3->setTelephone(478654321);
        $e3->setEmail('hello@designstudio.fr');
        $manager->persist($e3);
        $entreprises['design'] = $e3;

        $e4 = new Entreprise();
        $e4->setNomEntreprise('Fournisseur Général SAS');
        $e4->setSiret('55667788990011');
        $e4->setSiren('556677889');
        $e4->setNumeroVoie('321');
        $e4->setRue('Zone Industrielle Nord');
        $e4->setCodePostal(59000);
        $e4->setVille('Lille');
        $e4->setPays('France');
        $e4->setTelephone(320456789);
        $e4->setEmail('commandes@fournisseur-general.fr');
        $manager->persist($e4);
        $entreprises['fournisseur'] = $e4;

        $e5 = new Entreprise();
        $e5->setNomEntreprise('Web Agency Pro');
        $e5->setSiret('99887766554433');
        $e5->setSiren('998877665');
        $e5->setNumeroVoie('15bis');
        $e5->setRue('Rue de l\'Innovation');
        $e5->setCodePostal(33000);
        $e5->setVille('Bordeaux');
        $e5->setPays('France');
        $e5->setTelephone(556789012);
        $e5->setEmail('contact@webagency-pro.fr');
        $manager->persist($e5);
        $entreprises['webagency'] = $e5;

        // Entreprise spéciale pour le livret
        $entrepriseLivret = new Entreprise();
        $entrepriseLivret->setNomEntreprise('Livret');
        $entrepriseLivret->setSiret('00000000000000');
        $entrepriseLivret->setSiren('000000000');
        $entrepriseLivret->setRue('Compte épargne interne');
        $entrepriseLivret->setVille('Interne');
        $entrepriseLivret->setCodePostal(00000);
        $entrepriseLivret->setPays('France');
        $entrepriseLivret->setEmail('');
        $manager->persist($entrepriseLivret);
        $entreprises['livret'] = $entrepriseLivret;

        // ========== UTILISATEURS ==========
        $users = [];
        
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, 'admin123'));
        $userAdmin->setPersonne($admin);
        $userAdmin->addRole($roleAdmin);
        $manager->persist($userAdmin);
        $users['admin'] = $userAdmin;

        $userComptable = new User();
        $userComptable->setUsername('marie.comptable');
        $userComptable->setPassword($this->passwordHasher->hashPassword($userComptable, 'comptable123'));
        $userComptable->setPersonne($comptable);
        $userComptable->addRole($roleComptable);
        $manager->persist($userComptable);
        $users['comptable'] = $userComptable;

        $userConsultant = new User();
        $userConsultant->setUsername('consultant');
        $userConsultant->setPassword($this->passwordHasher->hashPassword($userConsultant, 'consultant123'));
        $userConsultant->setPersonne($personnes['dupont']);
        $userConsultant->addRole($roleConsultant);
        $manager->persist($userConsultant);
        $users['consultant'] = $userConsultant;

        // ========== EXERCICES ==========
        $exercices = [];
        
        // Exercice clôturé 2024
        $exercice2024 = new Exercice();
        $exercice2024->setLibelle('Exercice Comptable 2024');
        $exercice2024->setDateDebut(new \DateTime('2024-01-01'));
        $exercice2024->setDateFin(new \DateTime('2024-12-31'));
        $exercice2024->setNumeroOrdre(1);
        $exercice2024->setClos(true);
        $manager->persist($exercice2024);
        $exercices['2024'] = $exercice2024;

        // Exercice en cours 2025
        $exercice2025 = new Exercice();
        $exercice2025->setLibelle('Exercice Comptable 2025');
        $exercice2025->setDateDebut(new \DateTime('2025-01-01'));
        $exercice2025->setDateFin(new \DateTime('2025-12-31'));
        $exercice2025->setNumeroOrdre(2);
        $exercice2025->setClos(false);
        $manager->persist($exercice2025);
        $exercices['2025'] = $exercice2025;

        // Exercice futur 2026
        $exercice2026 = new Exercice();
        $exercice2026->setLibelle('Exercice Comptable 2026');
        $exercice2026->setDateDebut(new \DateTime('2026-01-01'));
        $exercice2026->setDateFin(new \DateTime('2026-12-31'));
        $exercice2026->setNumeroOrdre(3);
        $exercice2026->setClos(false);
        $manager->persist($exercice2026);
        $exercices['2026'] = $exercice2026;

        // ========== TYPES DE TRANSACTIONS ==========
        $typesTransaction = [];
        
        $typeVente = new TypeTransaction();
        $typeVente->setLibelle('Vente');
        $typeVente->setDescription('Vente de produits ou services');
        $typeVente->setTypeMontantAutorise('debit');
        $manager->persist($typeVente);
        $typesTransaction['vente'] = $typeVente;

        $typeAchat = new TypeTransaction();
        $typeAchat->setLibelle('Achat');
        $typeAchat->setDescription('Achat de marchandises ou services');
        $typeAchat->setTypeMontantAutorise('credit');
        $manager->persist($typeAchat);
        $typesTransaction['achat'] = $typeAchat;

        $typeLivret = new TypeTransaction();
        $typeLivret->setLibelle('Livret');
        $typeLivret->setDescription('Opérations sur le compte livret d\'épargne');
        $typeLivret->setTypeMontantAutorise('both');
        $manager->persist($typeLivret);
        $typesTransaction['livret'] = $typeLivret;

        $typeSalaire = new TypeTransaction();
        $typeSalaire->setLibelle('Salaire');
        $typeSalaire->setDescription('Paiement des salaires');
        $typeSalaire->setTypeMontantAutorise('credit');
        $manager->persist($typeSalaire);
        $typesTransaction['salaire'] = $typeSalaire;

        $typeCharge = new TypeTransaction();
        $typeCharge->setLibelle('Charges');
        $typeCharge->setDescription('Charges diverses (loyer, électricité, etc.)');
        $typeCharge->setTypeMontantAutorise('credit');
        $manager->persist($typeCharge);
        $typesTransaction['charge'] = $typeCharge;

        $typeBanque = new TypeTransaction();
        $typeBanque->setLibelle('Opération Bancaire');
        $typeBanque->setDescription('Frais bancaires, virements, etc.');
        $typeBanque->setTypeMontantAutorise('both');
        $manager->persist($typeBanque);
        $typesTransaction['banque'] = $typeBanque;

        // Flush pour avoir les IDs
        $manager->flush();

        // ========== TRANSACTIONS 2024 (EXERCICE CLÔTURÉ) ==========
        $numeroOrdre = 1;
        
        // Transactions de fin 2024
        $t1 = new Transaction();
        $t1->setLibelle('Vente services consulting décembre');
        $t1->setNumeroOrdre($numeroOrdre++);
        $t1->setDateTransaction(new \DateTime('2024-12-15'));
        $t1->setMontant('8500.00');
        $t1->setExercice($exercice2024);
        $t1->setTypeTransaction($typeVente);
        $t1->setEntreprise($entreprises['consulting']);
        $t1->setModeDePaiement($modes['facture']);
        $t1->setTypeCompte('compte_courant');
        $manager->persist($t1);

        $t2 = new Transaction();
        $t2->setLibelle('Achat matériel informatique');
        $t2->setNumeroOrdre($numeroOrdre++);
        $t2->setDateTransaction(new \DateTime('2024-12-20'));
        $t2->setMontant('-2300.00');
        $t2->setExercice($exercice2024);
        $t2->setTypeTransaction($typeAchat);
        $t2->setEntreprise($entreprises['fournisseur']);
        $t2->setModeDePaiement($modes['virement']);
        $t2->setTypeCompte('compte_courant');
        $manager->persist($t2);

        // ========== TRANSACTIONS 2025 (EXERCICE EN COURS) ==========
        $numeroOrdre = 1; // Reset pour 2025
        
        // Janvier 2025
        $t3 = new Transaction();
        $t3->setLibelle('Vente développement site web');
        $t3->setNumeroOrdre($numeroOrdre++);
        $t3->setDateTransaction(new \DateTime('2025-01-15'));
        $t3->setMontant('12000.00');
        $t3->setExercice($exercice2025);
        $t3->setTypeTransaction($typeVente);
        $t3->setEntreprise($entreprises['techcorp']);
        $t3->setModeDePaiement($modes['facture']);
        $t3->setTypeCompte('compte_courant');
        $manager->persist($t3);

        $t4 = new Transaction();
        $t4->setLibelle('Loyer bureau janvier');
        $t4->setNumeroOrdre($numeroOrdre++);
        $t4->setDateTransaction(new \DateTime('2025-01-31'));
        $t4->setMontant('-1800.00');
        $t4->setExercice($exercice2025);
        $t4->setTypeTransaction($typeCharge);
        $t4->setPersonne($personnes['dupont']);
        $t4->setModeDePaiement($modes['prelevement']);
        $t4->setTypeCompte('compte_courant');
        $manager->persist($t4);

        // Février 2025
        $t5 = new Transaction();
        $t5->setLibelle('Design logo et charte graphique');
        $t5->setNumeroOrdre($numeroOrdre++);
        $t5->setDateTransaction(new \DateTime('2025-02-10'));
        $t5->setMontant('3500.00');
        $t5->setExercice($exercice2025);
        $t5->setTypeTransaction($typeVente);
        $t5->setEntreprise($entreprises['design']);
        $t5->setModeDePaiement($modes['cheque']);
        $t5->setTypeCompte('compte_courant');
        $manager->persist($t5);

        $t6 = new Transaction();
        $t6->setLibelle('Salaire développeur février');
        $t6->setNumeroOrdre($numeroOrdre++);
        $t6->setDateTransaction(new \DateTime('2025-02-28'));
        $t6->setMontant('-3200.00');
        $t6->setExercice($exercice2025);
        $t6->setTypeTransaction($typeSalaire);
        $t6->setPersonne($personnes['bernard']);
        $t6->setModeDePaiement($modes['virement']);
        $t6->setTypeCompte('compte_courant');
        $manager->persist($t6);

        // Mars 2025
        $t7 = new Transaction();
        $t7->setLibelle('Vente formation développement');
        $t7->setNumeroOrdre($numeroOrdre++);
        $t7->setDateTransaction(new \DateTime('2025-03-05'));
        $t7->setMontant('2800.00');
        $t7->setExercice($exercice2025);
        $t7->setTypeTransaction($typeVente);
        $t7->setPersonne($personnes['martin']);
        $t7->setModeDePaiement($modes['cb']);
        $t7->setTypeCompte('compte_courant');
        $manager->persist($t7);

        $t8 = new Transaction();
        $t8->setLibelle('Frais bancaires mars');
        $t8->setNumeroOrdre($numeroOrdre++);
        $t8->setDateTransaction(new \DateTime('2025-03-31'));
        $t8->setMontant('-45.00');
        $t8->setExercice($exercice2025);
        $t8->setTypeTransaction($typeBanque);
        $t8->setModeDePaiement($modes['prelevement']);
        $t8->setTypeCompte('compte_courant');
        $manager->persist($t8);

        // Avril 2025
        $t9 = new Transaction();
        $t9->setLibelle('Maintenance site web mensuelle');
        $t9->setNumeroOrdre($numeroOrdre++);
        $t9->setDateTransaction(new \DateTime('2025-04-15'));
        $t9->setMontant('850.00');
        $t9->setExercice($exercice2025);
        $t9->setTypeTransaction($typeVente);
        $t9->setEntreprise($entreprises['webagency']);
        $t9->setModeDePaiement($modes['facture']);
        $t9->setTypeCompte('compte_courant');
        $manager->persist($t9);

        $t10 = new Transaction();
        $t10->setLibelle('Achat licences logiciels');
        $t10->setNumeroOrdre($numeroOrdre++);
        $t10->setDateTransaction(new \DateTime('2025-04-20'));
        $t10->setMontant('-1200.00');
        $t10->setExercice($exercice2025);
        $t10->setTypeTransaction($typeAchat);
        $t10->setEntreprise($entreprises['fournisseur']);
        $t10->setModeDePaiement($modes['cb']);
        $t10->setTypeCompte('compte_courant');
        $manager->persist($t10);

        // Mai 2025
        $t11 = new Transaction();
        $t11->setLibelle('Consultant projet e-commerce');
        $t11->setNumeroOrdre($numeroOrdre++);
        $t11->setDateTransaction(new \DateTime('2025-05-10'));
        $t11->setMontant('5500.00');
        $t11->setExercice($exercice2025);
        $t11->setTypeTransaction($typeVente);
        $t11->setPersonne($personnes['lefebvre']);
        $t11->setModeDePaiement($modes['virement']);
        $t11->setTypeCompte('compte_courant');
        $manager->persist($t11);

        // ========== TRANSACTIONS LIVRET ==========
        
        // Dépôts livret
        $tl1 = new Transaction();
        $tl1->setLibelle('DepotLivret 25-01-15-09h30');
        $tl1->setNumeroOrdre($numeroOrdre++);
        $tl1->setDateTransaction(new \DateTime('2025-01-15'));
        $tl1->setMontant('5000.00');
        $tl1->setExercice($exercice2025);
        $tl1->setTypeTransaction($typeLivret);
        $tl1->setEntreprise($entreprises['livret']);
        $tl1->setModeDePaiement($modes['livret']);
        $tl1->setTypeCompte('livret');
        $manager->persist($tl1);

        $tl2 = new Transaction();
        $tl2->setLibelle('DepotLivret 25-02-20-14h15');
        $tl2->setNumeroOrdre($numeroOrdre++);
        $tl2->setDateTransaction(new \DateTime('2025-02-20'));
        $tl2->setMontant('2500.00');
        $tl2->setExercice($exercice2025);
        $tl2->setTypeTransaction($typeLivret);
        $tl2->setEntreprise($entreprises['livret']);
        $tl2->setModeDePaiement($modes['livret']);
        $tl2->setTypeCompte('livret');
        $manager->persist($tl2);

        $tl3 = new Transaction();
        $tl3->setLibelle('DepotLivret 25-04-05-16h45');
        $tl3->setNumeroOrdre($numeroOrdre++);
        $tl3->setDateTransaction(new \DateTime('2025-04-05'));
        $tl3->setMontant('1800.00');
        $tl3->setExercice($exercice2025);
        $tl3->setTypeTransaction($typeLivret);
        $tl3->setEntreprise($entreprises['livret']);
        $tl3->setModeDePaiement($modes['livret']);
        $tl3->setTypeCompte('livret');
        $manager->persist($tl3);

        // Retraits livret
        $tl4 = new Transaction();
        $tl4->setLibelle('RetraitLivret 25-03-10-11h20');
        $tl4->setNumeroOrdre($numeroOrdre++);
        $tl4->setDateTransaction(new \DateTime('2025-03-10'));
        $tl4->setMontant('-1500.00');
        $tl4->setExercice($exercice2025);
        $tl4->setTypeTransaction($typeLivret);
        $tl4->setEntreprise($entreprises['livret']);
        $tl4->setModeDePaiement($modes['livret']);
        $tl4->setTypeCompte('livret');
        $manager->persist($tl4);

        // Transactions liées (Transfert compte courant <-> livret)
        $tTransfertDebit = new Transaction();
        $tTransfertDebit->setLibelle('Transfert vers livret');
        $tTransfertDebit->setNumeroOrdre($numeroOrdre++);
        $tTransfertDebit->setDateTransaction(new \DateTime('2025-05-15'));
        $tTransfertDebit->setMontant('-3000.00');
        $tTransfertDebit->setExercice($exercice2025);
        $tTransfertDebit->setTypeTransaction($typeBanque);
        $tTransfertDebit->setModeDePaiement($modes['virement']);
        $tTransfertDebit->setTypeCompte('compte_courant');
        $manager->persist($tTransfertDebit);

        $tTransfertCredit = new Transaction();
        $tTransfertCredit->setLibelle('Réception transfert du compte courant');
        $tTransfertCredit->setNumeroOrdre($numeroOrdre++);
        $tTransfertCredit->setDateTransaction(new \DateTime('2025-05-15'));
        $tTransfertCredit->setMontant('3000.00');
        $tTransfertCredit->setExercice($exercice2025);
        $tTransfertCredit->setTypeTransaction($typeLivret);
        $tTransfertCredit->setEntreprise($entreprises['livret']);
        $tTransfertCredit->setModeDePaiement($modes['livret']);
        $tTransfertCredit->setTypeCompte('livret');
        $manager->persist($tTransfertCredit);

        // Lier les transactions de transfert - on le fera après flush pour avoir les IDs
        $manager->flush();
        
        // Maintenant qu'on a les IDs, on peut lier les transactions
        $tTransfertDebit->setTransactionLieeId($tTransfertCredit->getIdTransaction());
        $tTransfertCredit->setTransactionLieeId($tTransfertDebit->getIdTransaction());

        // Transactions récentes (octobre 2025)
        $t12 = new Transaction();
        $t12->setLibelle('Développement application mobile');
        $t12->setNumeroOrdre($numeroOrdre++);
        $t12->setDateTransaction(new \DateTime('2025-10-01'));
        $t12->setMontant('15000.00');
        $t12->setExercice($exercice2025);
        $t12->setTypeTransaction($typeVente);
        $t12->setEntreprise($entreprises['techcorp']);
        $t12->setModeDePaiement($modes['facture']);
        $t12->setTypeCompte('compte_courant');
        $manager->persist($t12);

        $t13 = new Transaction();
        $t13->setLibelle('Formation équipe développement');
        $t13->setNumeroOrdre($numeroOrdre++);
        $t13->setDateTransaction(new \DateTime('2025-10-15'));
        $t13->setMontant('4200.00');
        $t13->setExercice($exercice2025);
        $t13->setTypeTransaction($typeVente);
        $t13->setPersonne($personnes['rousseau']);
        $t13->setModeDePaiement($modes['paypal']);
        $t13->setTypeCompte('compte_courant');
        $manager->persist($t13);

        $t14 = new Transaction();
        $t14->setLibelle('Charges sociales octobre');
        $t14->setNumeroOrdre($numeroOrdre++);
        $t14->setDateTransaction(new \DateTime('2025-10-20'));
        $t14->setMontant('-2800.00');
        $t14->setExercice($exercice2025);
        $t14->setTypeTransaction($typeCharge);
        $t14->setModeDePaiement($modes['prelevement']);
        $t14->setTypeCompte('compte_courant');
        $manager->persist($t14);

        $manager->flush();
    }
}