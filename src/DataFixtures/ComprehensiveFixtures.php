<?php

namespace App\DataFixtures;

use App\Entity\Personne;
use App\Entity\Entreprise;
use App\Entity\Exercice;
use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use App\Entity\ModeDePaiement;
use App\Entity\HistoriqueCloture;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ComprehensiveFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        echo "🚀 Chargement des fixtures complètes...\n";

        // ========== RÔLES ==========
        $roles = $this->createRoles($manager);
        echo "✅ Rôles créés\n";

        // ========== PERSONNES (D'ABORD) ==========
        $personnes = $this->createPersonnes($manager);
        echo "✅ Personnes créées\n";

        // ========== UTILISATEURS ==========
        $users = $this->createUsers($manager, $roles, $personnes);
        echo "✅ Utilisateurs créés\n";

        // ========== EXERCICES ==========
        $exercices = $this->createExercices($manager, $users);
        echo "✅ Exercices créés\n";

        // ========== MODES DE PAIEMENT ==========
        $modes = $this->createModesPaiement($manager);
        echo "✅ Modes de paiement créés\n";

        // ========== TYPES DE TRANSACTIONS ==========
        $types = $this->createTypesTransaction($manager);
        echo "✅ Types de transactions créés\n";

        // ========== ENTREPRISES ==========
        $entreprises = $this->createEntreprises($manager);
        echo "✅ Entreprises créées\n";

        // ========== TRANSACTIONS ==========
        $this->createTransactions($manager, $exercices, $types, $modes, $personnes, $entreprises);
        echo "✅ Transactions créées\n";

        // ========== HISTORIQUES DE CLÔTURE ==========
        $this->createHistoriquesCloture($manager, $exercices, $users);
        echo "✅ Historiques de clôture créés\n";

        $manager->flush();
        echo "🎉 Fixtures chargées avec succès!\n";
    }

    private function createRoles(ObjectManager $manager): array
    {
        $roles = [];

        $roleAdmin = new Role();
        $roleAdmin->setLibelle('ROLE_ADMIN');
        $roleAdmin->setDescription('Administrateur système');
        $manager->persist($roleAdmin);
        $roles['admin'] = $roleAdmin;

        $roleUser = new Role();
        $roleUser->setLibelle('ROLE_USER');
        $roleUser->setDescription('Utilisateur standard');
        $manager->persist($roleUser);
        $roles['user'] = $roleUser;

        return $roles;
    }

    private function createUsers(ObjectManager $manager, array $roles, array $personnes): array
    {
        $users = [];

        // Admin
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setPersonne($personnes['dupont']);
        $admin->addRole($roles['admin']);
        $admin->addRole($roles['user']);
        $manager->persist($admin);
        $users['admin'] = $admin;

        // Trésorier
        $tresorier = new User();
        $tresorier->setUsername('tresorier');
        $tresorier->setPassword($this->passwordHasher->hashPassword($tresorier, 'tresorier123'));
        $tresorier->setPersonne($personnes['martin']);
        $tresorier->addRole($roles['user']);
        $manager->persist($tresorier);
        $users['tresorier'] = $tresorier;

        // Secrétaire
        $secretaire = new User();
        $secretaire->setUsername('secretaire');
        $secretaire->setPassword($this->passwordHasher->hashPassword($secretaire, 'secretaire123'));
        $secretaire->setPersonne($personnes['dubois']);
        $secretaire->addRole($roles['user']);
        $manager->persist($secretaire);
        $users['secretaire'] = $secretaire;

        return $users;
    }

    private function createExercices(ObjectManager $manager, array $users): array
    {
        $exercices = [];

        // Exercice 2023 (clôturé)
        $ex2023 = new Exercice();
        $ex2023->setLibelle('Exercice Comptable 2023');
        $ex2023->setDateDebut(new \DateTime('2023-01-01'));
        $ex2023->setDateFin(new \DateTime('2023-12-31'));
        $ex2023->setNumeroOrdre(1);
        $ex2023->setClos(true);
        $manager->persist($ex2023);
        $exercices['2023'] = $ex2023;

        // Exercice 2024 (clôturé)
        $ex2024 = new Exercice();
        $ex2024->setLibelle('Exercice Comptable 2024');
        $ex2024->setDateDebut(new \DateTime('2024-01-01'));
        $ex2024->setDateFin(new \DateTime('2024-12-31'));
        $ex2024->setNumeroOrdre(2);
        $ex2024->setClos(true);
        $manager->persist($ex2024);
        $exercices['2024'] = $ex2024;

        // Exercice 2025 (en cours)
        $ex2025 = new Exercice();
        $ex2025->setLibelle('Exercice Comptable 2025');
        $ex2025->setDateDebut(new \DateTime('2025-01-01'));
        $ex2025->setDateFin(new \DateTime('2025-12-31'));
        $ex2025->setNumeroOrdre(3);
        $ex2025->setClos(false);
        $manager->persist($ex2025);
        $exercices['2025'] = $ex2025;

        // Exercice 2026 (futur)
        $ex2026 = new Exercice();
        $ex2026->setLibelle('Exercice Comptable 2026');
        $ex2026->setDateDebut(new \DateTime('2026-01-01'));
        $ex2026->setDateFin(new \DateTime('2026-12-31'));
        $ex2026->setNumeroOrdre(4);
        $ex2026->setClos(false);
        $manager->persist($ex2026);
        $exercices['2026'] = $ex2026;

        return $exercices;
    }

    private function createModesPaiement(ObjectManager $manager): array
    {
        $modes = [];

        $modesPaiementData = [
            ['especes', 'Espèces', 'Paiement en liquide'],
            ['cheque', 'Chèque', 'Paiement par chèque bancaire'],
            ['virement', 'Virement', 'Virement bancaire'],
            ['carte_bancaire', 'Carte bancaire', 'Paiement par carte bancaire'],
            ['prelevement', 'Prélèvement', 'Prélèvement automatique'],
            ['livret', 'Livret', 'Opération sur livret d\'épargne'],
            ['facture', 'Facture', 'Paiement sur facture (délai)'],
        ];

        foreach ($modesPaiementData as [$code, $libelle, $description]) {
            $mode = new ModeDePaiement();
            $mode->setLibelle($libelle);
            $manager->persist($mode);
            $modes[$code] = $mode;
        }

        return $modes;
    }

    private function createTypesTransaction(ObjectManager $manager): array
    {
        $types = [];

        $typesData = [
            ['cotisation', 'Cotisation', 'Cotisations des membres (ouvre droit aux attestations fiscales)', 'credit'],
            ['subvention', 'Subvention', 'Subventions publiques et privées', 'credit'],
            ['sponsoring', 'Sponsoring', 'Partenariats et sponsoring', 'credit'],
            ['vente_evenement', 'Vente événement', 'Ventes lors d\'événements (buvette, tombola, etc.)', 'credit'],
            ['don', 'Don', 'Dons et mécénat', 'credit'],
            ['remboursement', 'Remboursement', 'Remboursements divers', 'both'],
            ['achat_equipement', 'Achat équipement', 'Achat matériel et équipements sportifs', 'debit'],
            ['frais_deplacement', 'Frais déplacement', 'Frais de déplacement et hébergement', 'debit'],
            ['assurance', 'Assurance', 'Assurances et licences fédérales', 'debit'],
            ['maintenance', 'Maintenance', 'Maintenance et réparations', 'debit'],
            ['communication', 'Communication', 'Frais de communication et marketing', 'debit'],
            ['administration', 'Administration', 'Frais administratifs et bancaires', 'debit'],
            ['formation', 'Formation', 'Formation des entraîneurs et dirigeants', 'debit'],
            ['arbitrage', 'Arbitrage', 'Frais d\'arbitrage et organisation', 'debit'],
            ['transfert_livret', 'Transfert livret', 'Transferts vers/depuis le livret d\'épargne', 'both'],
        ];

        foreach ($typesData as [$code, $libelle, $description, $montantAutorise]) {
            $type = new TypeTransaction();
            $type->setLibelle($libelle);
            $type->setDescription($description);
            $type->setTypeMontantAutorise($montantAutorise);
            $manager->persist($type);
            $types[$code] = $type;
        }

        return $types;
    }

    private function createPersonnes(ObjectManager $manager): array
    {
        $personnes = [];

        $personnesData = [
            ['M.', 'Dupont', 'Jean', '123 rue de la Paix', 'Paris', 75001, '0123456789', 'jean.dupont@email.fr'],
            ['Mme', 'Martin', 'Marie', '456 avenue des Champs', 'Lyon', 69000, '0234567890', 'marie.martin@email.fr'],
            ['M.', 'Bernard', 'Pierre', '789 boulevard Saint-Michel', 'Marseille', 13000, '0345678901', 'pierre.bernard@email.fr'],
            ['Mlle', 'Dubois', 'Sophie', '321 rue Victor Hugo', 'Toulouse', 31000, '0456789012', 'sophie.dubois@email.fr'],
            ['M.', 'Moreau', 'Antoine', '654 place de la République', 'Nice', 06000, '0567890123', 'antoine.moreau@email.fr'],
            ['Mme', 'Petit', 'Claire', '987 rue Nationale', 'Lille', 59000, '0678901234', 'claire.petit@email.fr'],
            ['M.', 'Durand', 'François', '147 avenue de la Liberté', 'Strasbourg', 67000, '0789012345', 'francois.durand@email.fr'],
            ['Mme', 'Leroy', 'Isabelle', '258 rue de Rivoli', 'Nantes', 44000, '0890123456', 'isabelle.leroy@email.fr'],
            ['M.', 'Roux', 'Thomas', '369 cours Lafayette', 'Bordeaux', 33000, '0901234567', 'thomas.roux@email.fr'],
            ['Mme', 'Vincent', 'Caroline', '741 rue de la Gare', 'Montpellier', 34000, '0912345678', 'caroline.vincent@email.fr'],
        ];

        foreach ($personnesData as [$civilite, $nom, $prenom, $adresse, $ville, $codePostal, $telephone, $email]) {
            $personne = new Personne();
            $personne->setCivilite($civilite);
            $personne->setNom($nom);
            $personne->setPrenom($prenom);
            $personne->setRue($adresse);
            $personne->setVille($ville);
            $personne->setCodePostal($codePostal);
            $personne->setTelephone(intval($telephone));
            $personne->setEmail($email);
            
            $manager->persist($personne);
            $personnes[strtolower($nom)] = $personne;
        }

        return $personnes;
    }

    private function createEntreprises(ObjectManager $manager): array
    {
        $entreprises = [];

        $entreprisesData = [
            ['Mairie de Toulouse', '21310555600019', '213105556', '1 place du Capitole', 'Toulouse', 31000, '0561223344', 'mairie@toulouse.fr'],
            ['Région Occitanie', '23310000400019', '233100004', '22 boulevard du Maréchal Juin', 'Toulouse', 31406, '0567788990', 'contact@laregion.fr'],
            ['Crédit Agricole Toulouse', '78142420273', '781424202', '15 place Wilson', 'Toulouse', 31000, '0825005050', 'toulouse@ca-toulouse.fr'],
            ['Décathlon Toulouse', '30235032800156', '302350328', 'ZAC de Gramont', 'Toulouse', 31200, '0562200300', 'toulouse@decathlon.fr'],
            ['Intersport Toulouse', '32584502400019', '325845024', '2 rue de Metz', 'Toulouse', 31000, '0561123456', 'contact@intersport-toulouse.fr'],
            ['Fédération Française de Rugby', '77568151300042', '775681513', '9 rue de Liège', 'Paris', 75009, '0153218200', 'ffr@ffr.fr'],
            ['Ligue Régionale Occitanie', '35248759600028', '352487596', '123 avenue de Muret', 'Toulouse', 31300, '0561456789', 'ligue@occitanie-rugby.fr'],
            ['Gilbert Rugby', '31245896700015', '312458967', 'Zone Industrielle', 'Bourgoin-Jallieu', 38300, '0474282828', 'info@gilbert.fr'],
            ['Assurance MAIF', '77567227200394', '775672272', '200 avenue Salvador Allende', 'Niort', 79000, '0549733373', 'toulouse@maif.fr'],
            ['Livret Club RCA', '00000000000000', '000000000', 'Compte épargne club', 'Toulouse', 31000, '', 'livret@rca-toulouse.fr'],
        ];

        foreach ($entreprisesData as [$nom, $siret, $siren, $adresse, $ville, $codePostal, $telephone, $email]) {
            $entreprise = new Entreprise();
            $entreprise->setNomEntreprise($nom);
            $entreprise->setSiret($siret);
            $entreprise->setSiren($siren);
            $entreprise->setRue($adresse);
            $entreprise->setVille($ville);
            $entreprise->setCodePostal($codePostal);
            if (!empty($telephone)) {
                $entreprise->setTelephone(intval(str_replace(' ', '', $telephone)));
            }
            $entreprise->setEmail($email);
            $manager->persist($entreprise);
            
            $key = strtolower(str_replace(['é', 'è', ' ', '-'], ['e', 'e', '_', '_'], explode(' ', $nom)[0]));
            $entreprises[$key] = $entreprise;
        }

        return $entreprises;
    }

    private function createTransactions(ObjectManager $manager, array $exercices, array $types, array $modes, array $personnes, array $entreprises): void
    {
        // ========== TRANSACTIONS 2023 (CLÔTURÉ) ==========
        $this->createTransactions2023($manager, $exercices['2023'], $types, $modes, $personnes, $entreprises);
        
        // ========== TRANSACTIONS 2024 (CLÔTURÉ) ==========
        $this->createTransactions2024($manager, $exercices['2024'], $types, $modes, $personnes, $entreprises);
        
        // ========== TRANSACTIONS 2025 (EN COURS) ==========
        $this->createTransactions2025($manager, $exercices['2025'], $types, $modes, $personnes, $entreprises);
    }

    private function createTransactions2023(ObjectManager $manager, Exercice $exercice, array $types, array $modes, array $personnes, array $entreprises): void
    {
        $numeroOrdre = 1;

        // Janvier 2023 - Début d'exercice
        $transactions = [
            ['Cotisation annuelle Jean Dupont', new \DateTime('2023-01-15'), '180.00', $types['cotisation'], $modes['virement'], $personnes['dupont'], null],
            ['Cotisation annuelle Marie Martin', new \DateTime('2023-01-20'), '180.00', $types['cotisation'], $modes['cheque'], $personnes['martin'], null],
            ['Subvention municipale 2023', new \DateTime('2023-01-25'), '5000.00', $types['subvention'], $modes['virement'], null, $entreprises['mairie']],
            
            // Février 2023
            ['Achat maillots équipe première', new \DateTime('2023-02-10'), '-1250.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['decathlon']],
            ['Cotisation Pierre Bernard', new \DateTime('2023-02-14'), '180.00', $types['cotisation'], $modes['especes'], $personnes['bernard'], null],
            ['Frais bancaires janvier', new \DateTime('2023-02-28'), '-15.50', $types['administration'], $modes['prelevement'], null, $entreprises['credit']],
            
            // Mars 2023
            ['Sponsoring local Intersport', new \DateTime('2023-03-05'), '800.00', $types['sponsoring'], $modes['virement'], null, $entreprises['intersport']],
            ['Assurance responsabilité civile', new \DateTime('2023-03-12'), '-450.00', $types['assurance'], $modes['prelevement'], null, $entreprises['assurance']],
            ['Cotisation Sophie Dubois', new \DateTime('2023-03-18'), '180.00', $types['cotisation'], $modes['cheque'], $personnes['dubois'], null],
            
            // Transfert vers livret en mars
            ['Transfert vers livret épargne', new \DateTime('2023-03-25'), '-3000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            ['Dépôt livret épargne', new \DateTime('2023-03-25'), '3000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            
            // Reste de l'année 2023 (résumé)
            ['Tournoi été - Recettes buvette', new \DateTime('2023-06-15'), '1200.00', $types['vente_evenement'], $modes['especes'], null, null],
            ['Déplacement championnat région', new \DateTime('2023-06-20'), '-800.00', $types['frais_deplacement'], $modes['carte_bancaire'], null, null],
            ['Subvention régionale', new \DateTime('2023-09-10'), '2500.00', $types['subvention'], $modes['virement'], null, $entreprises['region']],
            ['Maintenance terrain automne', new \DateTime('2023-10-15'), '-950.00', $types['maintenance'], $modes['facture'], null, null],
            ['Formation arbitres', new \DateTime('2023-11-20'), '-300.00', $types['formation'], $modes['virement'], null, $entreprises['federation']],
            ['Cotisations tardives décembre', new \DateTime('2023-12-15'), '540.00', $types['cotisation'], $modes['cheque'], $personnes['moreau'], null],
        ];

        foreach ($transactions as [$libelle, $date, $montant, $type, $mode, $personne, $entreprise]) {
            $transaction = new Transaction();
            $transaction->setLibelle($libelle);
            $transaction->setNumeroOrdre($numeroOrdre++);
            $transaction->setDateTransaction($date);
            $transaction->setMontant($montant);
            $transaction->setExercice($exercice);
            $transaction->setTypeTransaction($type);
            $transaction->setModeDePaiement($mode);
            
            if ($personne) {
                $transaction->setPersonne($personne);
            }
            if ($entreprise) {
                $transaction->setEntreprise($entreprise);
            }
            
            // Gérer les transactions livret
            if ($type === $types['transfert_livret'] && $entreprise === $entreprises['livret']) {
                $transaction->setTypeCompte('livret');
            } else {
                $transaction->setTypeCompte('compte_courant');
            }
            
            $manager->persist($transaction);
        }
    }

    private function createTransactions2024(ObjectManager $manager, Exercice $exercice, array $types, array $modes, array $personnes, array $entreprises): void
    {
        $numeroOrdre = 1;

        $transactions = [
            // Janvier 2024
            ['Cotisations janvier 2024 - Lot 1', new \DateTime('2024-01-10'), '720.00', $types['cotisation'], $modes['virement'], $personnes['dupont'], null],
            ['Subvention municipale 2024', new \DateTime('2024-01-15'), '5500.00', $types['subvention'], $modes['virement'], null, $entreprises['mairie']],
            ['Licence fédérale équipe', new \DateTime('2024-01-20'), '-890.00', $types['assurance'], $modes['prelevement'], null, $entreprises['federation']],
            
            // Février 2024
            ['Remboursement déplacement entraîneur', new \DateTime('2024-02-05'), '-125.50', $types['frais_deplacement'], $modes['especes'], $personnes['bernard'], null],
            ['Sponsoring Décathlon 2024', new \DateTime('2024-02-12'), '1200.00', $types['sponsoring'], $modes['virement'], null, $entreprises['decathlon']],
            ['Achat ballons entraînement', new \DateTime('2024-02-18'), '-285.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['gilbert']],
            
            // Mars 2024 - Gros transfert vers livret
            ['Transfert exceptionnel livret', new \DateTime('2024-03-15'), '-8000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            ['Placement livret exceptionnel', new \DateTime('2024-03-15'), '8000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            
            // Avril à décembre 2024 (transactions variées)
            ['Don mécène local', new \DateTime('2024-04-20'), '1500.00', $types['don'], $modes['virement'], null, null],
            ['Maintenance équipements', new \DateTime('2024-05-10'), '-750.00', $types['maintenance'], $modes['facture'], null, null],
            ['Tournoi jeunes - Recettes', new \DateTime('2024-06-15'), '2100.00', $types['vente_evenement'], $modes['especes'], null, null],
            ['Frais arbitrage championnat', new \DateTime('2024-06-20'), '-450.00', $types['arbitrage'], $modes['carte_bancaire'], null, $entreprises['ligue']],
            ['Cotisations été 2024', new \DateTime('2024-07-01'), '1080.00', $types['cotisation'], $modes['cheque'], $personnes['petit'], null],
            ['Communication site web', new \DateTime('2024-08-15'), '-380.00', $types['communication'], $modes['prelevement'], null, null],
            ['Subvention région automne', new \DateTime('2024-09-25'), '3200.00', $types['subvention'], $modes['virement'], null, $entreprises['region']],
            ['Formation dirigeants', new \DateTime('2024-10-12'), '-580.00', $types['formation'], $modes['virement'], null, $entreprises['federation']],
            ['Déplacement finale régionale', new \DateTime('2024-11-08'), '-1150.00', $types['frais_deplacement'], $modes['carte_bancaire'], null, null],
            ['Cotisations fin d\'année', new \DateTime('2024-12-20'), '900.00', $types['cotisation'], $modes['virement'], $personnes['vincent'], null],
            
            // Transaction négative inhabituelle
            ['Remboursement cotisation - erreur', new \DateTime('2024-12-28'), '-180.00', $types['remboursement'], $modes['virement'], $personnes['durand'], null],
        ];

        foreach ($transactions as [$libelle, $date, $montant, $type, $mode, $personne, $entreprise]) {
            $transaction = new Transaction();
            $transaction->setLibelle($libelle);
            $transaction->setNumeroOrdre($numeroOrdre++);
            $transaction->setDateTransaction($date);
            $transaction->setMontant($montant);
            $transaction->setExercice($exercice);
            $transaction->setTypeTransaction($type);
            $transaction->setModeDePaiement($mode);
            
            if ($personne) {
                $transaction->setPersonne($personne);
            }
            if ($entreprise) {
                $transaction->setEntreprise($entreprise);
            }
            
            if ($type === $types['transfert_livret'] && $entreprise === $entreprises['livret']) {
                $transaction->setTypeCompte('livret');
            } else {
                $transaction->setTypeCompte('compte_courant');
            }
            
            $manager->persist($transaction);
        }
    }

    private function createTransactions2025(ObjectManager $manager, Exercice $exercice, array $types, array $modes, array $personnes, array $entreprises): void
    {
        $numeroOrdre = 1;

        $transactions = [
            // Janvier 2025 - Début d'année
            ['Cotisations nouvelle saison - Dupont', new \DateTime('2025-01-08'), '200.00', $types['cotisation'], $modes['virement'], $personnes['dupont'], null],
            ['Cotisations nouvelle saison - Martin', new \DateTime('2025-01-08'), '200.00', $types['cotisation'], $modes['prelevement'], $personnes['martin'], null],
            ['Subvention municipale 2025', new \DateTime('2025-01-12'), '6000.00', $types['subvention'], $modes['virement'], null, $entreprises['mairie']],
            ['Frais bancaires décembre', new \DateTime('2025-01-15'), '-18.50', $types['administration'], $modes['prelevement'], null, $entreprises['credit']],
            ['Licence FFR saison 2025', new \DateTime('2025-01-20'), '-1200.00', $types['assurance'], $modes['prelevement'], null, $entreprises['federation']],
            
            // Février 2025
            ['Cotisations février - Bernard', new \DateTime('2025-02-03'), '200.00', $types['cotisation'], $modes['cheque'], $personnes['bernard'], null],
            ['Cotisations février - Dubois', new \DateTime('2025-02-03'), '200.00', $types['cotisation'], $modes['carte_bancaire'], $personnes['dubois'], null],
            ['Achat équipement hiver', new \DateTime('2025-02-10'), '-850.00', $types['achat_equipement'], $modes['facture'], null, $entreprises['gilbert']],
            ['Remboursement essence déplacement', new \DateTime('2025-02-14'), '-95.00', $types['frais_deplacement'], $modes['especes'], $personnes['moreau'], null],
            ['Sponsoring annuel Intersport', new \DateTime('2025-02-20'), '1500.00', $types['sponsoring'], $modes['virement'], null, $entreprises['intersport']],
            
            // Mars 2025
            ['Cotisations mars - Petit', new \DateTime('2025-03-02'), '200.00', $types['cotisation'], $modes['virement'], $personnes['petit'], null],
            ['Cotisations mars - Durand', new \DateTime('2025-03-05'), '200.00', $types['cotisation'], $modes['cheque'], $personnes['durand'], null],
            ['Maintenance terrain printemps', new \DateTime('2025-03-12'), '-680.00', $types['maintenance'], $modes['carte_bancaire'], null, null],
            ['Assurance matériel club', new \DateTime('2025-03-18'), '-520.00', $types['assurance'], $modes['prelevement'], null, $entreprises['assurance']],
            ['Formation secourisme', new \DateTime('2025-03-25'), '-240.00', $types['formation'], $modes['virement'], null, null],
            
            // Avril 2025
            ['Cotisations avril - Leroy', new \DateTime('2025-04-07'), '200.00', $types['cotisation'], $modes['prelevement'], $personnes['leroy'], null],
            ['Cotisations avril - Roux', new \DateTime('2025-04-10'), '200.00', $types['cotisation'], $modes['virement'], $personnes['roux'], null],
            ['Don généreux supporter', new \DateTime('2025-04-15'), '500.00', $types['don'], $modes['cheque'], null, null],
            ['Communication flyers tournoi', new \DateTime('2025-04-20'), '-180.00', $types['communication'], $modes['carte_bancaire'], null, null],
            
            // Mai 2025
            ['Cotisations mai - Vincent', new \DateTime('2025-05-05'), '200.00', $types['cotisation'], $modes['carte_bancaire'], $personnes['vincent'], null],
            ['Subvention région Occitanie', new \DateTime('2025-05-10'), '4200.00', $types['subvention'], $modes['virement'], null, $entreprises['region']],
            ['Déplacement équipe jeunes', new \DateTime('2025-05-15'), '-320.00', $types['frais_deplacement'], $modes['especes'], null, null],
            ['Achat matériel médical', new \DateTime('2025-05-22'), '-165.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['decathlon']],
            
            // Juin 2025 - Tournoi d'été
            ['Tournoi été - Inscriptions', new \DateTime('2025-06-01'), '1800.00', $types['vente_evenement'], $modes['especes'], null, null],
            ['Tournoi été - Buvette samedi', new \DateTime('2025-06-07'), '450.00', $types['vente_evenement'], $modes['especes'], null, null],
            ['Tournoi été - Buvette dimanche', new \DateTime('2025-06-08'), '380.00', $types['vente_evenement'], $modes['especes'], null, null],
            ['Frais organisation tournoi', new \DateTime('2025-06-10'), '-420.00', $types['arbitrage'], $modes['carte_bancaire'], null, $entreprises['ligue']],
            
            // Juillet 2025
            ['Transfert vers livret été', new \DateTime('2025-07-05'), '-5000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            ['Dépôt livret été 2025', new \DateTime('2025-07-05'), '5000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            ['Maintenance équipement été', new \DateTime('2025-07-12'), '-280.00', $types['maintenance'], $modes['facture'], null, null],
            
            // Août - Septembre 2025
            ['Préparation nouvelle saison', new \DateTime('2025-08-20'), '-750.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['gilbert']],
            ['Stage été entraîneurs', new \DateTime('2025-08-25'), '-490.00', $types['formation'], $modes['virement'], null, $entreprises['federation']],
            ['Retrait partiel livret', new \DateTime('2025-09-10'), '2000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            ['Prélèvement partiel livret', new \DateTime('2025-09-10'), '-2000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],
            
            // Octobre 2025 - Transactions récentes
            ['Sponsoring nouveau partenaire', new \DateTime('2025-10-05'), '2500.00', $types['sponsoring'], $modes['virement'], null, null],
            ['Réparation vestiaires', new \DateTime('2025-10-12'), '-1200.00', $types['maintenance'], $modes['facture'], null, null],
            ['Cotisations rattrapage octobre', new \DateTime('2025-10-20'), '600.00', $types['cotisation'], $modes['cheque'], null, null],
            ['Frais déplacement octobre', new \DateTime('2025-10-25'), '-180.00', $types['frais_deplacement'], $modes['carte_bancaire'], null, null],
            
            // Novembre 2025 - Plus récent
            ['Don club partenaire', new \DateTime('2025-11-02'), '800.00', $types['don'], $modes['virement'], null, null],
            ['Achat maillots nouveaux', new \DateTime('2025-11-08'), '-950.00', $types['achat_equipement'], $modes['facture'], null, $entreprises['decathlon']],
            ['Formation arbitres novembre', new \DateTime('2025-11-15'), '-320.00', $types['formation'], $modes['carte_bancaire'], null, $entreprises['ligue']],
            ['Remboursement frais médical', new \DateTime('2025-11-20'), '-85.00', $types['remboursement'], $modes['especes'], $personnes['bernard'], null],
        ];

        foreach ($transactions as [$libelle, $date, $montant, $type, $mode, $personne, $entreprise]) {
            $transaction = new Transaction();
            $transaction->setLibelle($libelle);
            $transaction->setNumeroOrdre($numeroOrdre++);
            $transaction->setDateTransaction($date);
            $transaction->setMontant($montant);
            $transaction->setExercice($exercice);
            $transaction->setTypeTransaction($type);
            $transaction->setModeDePaiement($mode);
            
            if ($personne) {
                $transaction->setPersonne($personne);
            }
            if ($entreprise) {
                $transaction->setEntreprise($entreprise);
            }
            
            if ($type === $types['transfert_livret'] && $entreprise === $entreprises['livret']) {
                $transaction->setTypeCompte('livret');
            } else {
                $transaction->setTypeCompte('compte_courant');
            }
            
            $manager->persist($transaction);
        }
    }

    private function createHistoriquesCloture(ObjectManager $manager, array $exercices, array $users): void
    {
        // Clôture de l'exercice 2023
        $hist2023_cloture = new HistoriqueCloture();
        $hist2023_cloture->setExercice($exercices['2023']);
        $hist2023_cloture->setDateAction(new \DateTime('2024-01-31 15:30:00'));
        $hist2023_cloture->setTypeAction('CLOTURE');
        $hist2023_cloture->setUser($users['tresorier']);
        $hist2023_cloture->setCommentaire('Clôture de l\'exercice 2023 après validation du bilan comptable en AG.');
        $manager->persist($hist2023_cloture);

        // Clôture et déclôture de l'exercice 2024 (exemple de correction)
        $hist2024_cloture = new HistoriqueCloture();
        $hist2024_cloture->setExercice($exercices['2024']);
        $hist2024_cloture->setDateAction(new \DateTime('2024-12-15 10:15:00'));
        $hist2024_cloture->setTypeAction('CLOTURE');
        $hist2024_cloture->setUser($users['admin']);
        $hist2024_cloture->setCommentaire('Clôture anticipée de l\'exercice 2024.');
        $manager->persist($hist2024_cloture);

        $hist2024_decloture = new HistoriqueCloture();
        $hist2024_decloture->setExercice($exercices['2024']);
        $hist2024_decloture->setDateAction(new \DateTime('2024-12-20 14:45:00'));
        $hist2024_decloture->setTypeAction('DECLOTURE');
        $hist2024_decloture->setUser($users['tresorier']);
        $hist2024_decloture->setCommentaire('Déclôture pour correction d\'une transaction oubliée (remboursement cotisation).');
        $manager->persist($hist2024_decloture);

        $hist2024_cloture_finale = new HistoriqueCloture();
        $hist2024_cloture_finale->setExercice($exercices['2024']);
        $hist2024_cloture_finale->setDateAction(new \DateTime('2025-01-05 09:20:00'));
        $hist2024_cloture_finale->setTypeAction('CLOTURE');
        $hist2024_cloture_finale->setUser($users['admin']);
        $hist2024_cloture_finale->setCommentaire('Clôture définitive de l\'exercice 2024 après corrections.');
        $manager->persist($hist2024_cloture_finale);
    }
}