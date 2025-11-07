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

        // ========== ENTREPRISE ==========
        $entreprise = $this->createEntreprise($manager);
        echo "✅ Entreprise créée\n";

        // ========== PERSONNES ==========
        $personnes = $this->createPersonnes($manager);
        echo "✅ Personnes créées\n";

        // ========== USERS ==========
        $users = $this->createUsers($manager, $roles, $personnes);
        echo "✅ Utilisateurs créés\n";

        // ========== MODES DE PAIEMENT ==========
        $modesPaiement = $this->createModesPaiement($manager);
        echo "✅ Modes de paiement créés\n";

        // ========== TYPES DE TRANSACTION ==========
        $types = $this->createTypesTransaction($manager);
        echo "✅ Types de transaction créés\n";

        // ========== EXERCICES ==========
        $exercices = $this->createExercices($manager);
        echo "✅ Exercices créés\n";

        // ========== TRANSACTIONS ==========
        $this->createTransactions($manager, $exercices, $personnes, $types, $modesPaiement);
        echo "✅ Transactions créées\n";

        $manager->flush();
        echo "🎉 Fixtures chargées avec succès !\n";
    }

    private function createRoles(ObjectManager $manager): array
    {
        $roles = [];
        $rolesData = [
            ['libelle' => 'USER', 'description' => 'Utilisateur'],
            ['libelle' => 'ADMIN', 'description' => 'Administrateur'],
            ['libelle' => 'SUPER_ADMIN', 'description' => 'Super Administrateur'],
        ];

        foreach ($rolesData as $data) {
            $role = new Role();
            $role->setLibelle($data['libelle']);
            $role->setDescription($data['description']);
            $role->setHierarchyLevel($data['libelle'] === 'SUPER_ADMIN' ? 100 : ($data['libelle'] === 'ADMIN' ? 75 : 50));
            $manager->persist($role);
            $roles[] = $role;
        }

        return $roles;
    }

    private function createEntreprise(ObjectManager $manager): Entreprise
    {
        $entreprise = new Entreprise();
        $entreprise->setNomEntreprise('AMICALE DES ANCIENS DU RUGBY CLUB AUDOMAROIS')
            ->setRue('ALLEE DES SPORTS')
            ->setVille('SAINT OMER')
            ->setCodePostal(62500)
            ->setPays('France');

        $manager->persist($entreprise);
        return $entreprise;
    }

    private function createPersonnes(ObjectManager $manager): array
    {
        $personnes = [];
        $personnesData = [
            ['nom' => 'GAUTHEROT', 'prenom' => 'Didier', 'civilite' => 'Mr', 'rue' => '188, rue de Dunkerque', 'ville' => 'St Omer', 'code_postal' => 62500, 'email' => null],
            ['nom' => 'HERBST', 'prenom' => 'David', 'civilite' => 'Mr', 'rue' => '9 rue de la Chapelle', 'ville' => 'Tilques', 'code_postal' => 62500, 'email' => 'david-herbst@orange.fr'],
            ['nom' => 'MARTIN', 'prenom' => 'Louis', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],
            ['nom' => 'OBERT', 'prenom' => 'Armand', 'civilite' => 'Mr', 'rue' => '1, rue des prés fleuris', 'ville' => 'WITTES', 'code_postal' => 62120, 'email' => 'armand.obert@yahoo.fr'],
            ['nom' => 'ROUSSEL', 'prenom' => 'Etienne', 'civilite' => 'Mr', 'rue' => '2 domaine des Genets', 'ville' => 'LONGUENESSE', 'code_postal' => 62219, 'email' => 'monique.etienne-roussel@wanadoo.fr'],
            ['nom' => 'BLONDE', 'prenom' => 'Guy', 'civilite' => 'Mr', 'rue' => '7, rue du marais', 'ville' => 'Moulle', 'code_postal' => 62910, 'email' => 'guy.blonde@free.fr'],
            ['nom' => 'ADMIN', 'prenom' => 'Admin', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => 'admin@rca-amicale.fr'],
        ];

        foreach ($personnesData as $data) {
            $personne = new Personne();
            $personne->setNom($data['nom']);
            $personne->setPrenom($data['prenom']);
            $personne->setCivilite($data['civilite']);
            if ($data['rue']) $personne->setRue($data['rue']);
            if ($data['ville']) $personne->setVille($data['ville']);
            if ($data['code_postal']) $personne->setCodePostal($data['code_postal']);
            if ($data['email']) $personne->setEmail($data['email']);

            $manager->persist($personne);
            $personnes[] = $personne;
        }

        return $personnes;
    }

    private function createUsers(ObjectManager $manager, array $roles, array $personnes): array
    {
        $users = [];
        
        // Admin user
        $adminUser = new User();
        $adminUser->setUsername('admin');
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'admin123'));
        $adminUser->setPersonne($personnes[6]); // ADMIN personne
        $adminUser->addRole($roles[2]); // ROLE_SUPER_ADMIN
        $manager->persist($adminUser);
        $users[] = $adminUser;

        // Regular users
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setUsername('user' . ($i + 1));
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setPersonne($personnes[$i]);
            $user->addRole($roles[0]); // ROLE_USER
            $manager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    private function createModesPaiement(ObjectManager $manager): array
    {
        $modes = [];
        $modesData = ['Espèces', 'Chèque', 'Virement', 'Carte bancaire', 'Prélèvement'];

        foreach ($modesData as $nom) {
            $mode = new ModeDePaiement();
            $mode->setLibelle($nom);
            $manager->persist($mode);
            $modes[] = $mode;
        }

        return $modes;
    }

    private function createTypesTransaction(ObjectManager $manager): array
    {
        $types = [];
        $typesData = [
            ['libelle' => 'Cotisation', 'description' => 'Cotisation annuelle des membres'],
            ['libelle' => 'Don', 'description' => 'Don des membres'],
            ['libelle' => 'Subvention', 'description' => 'Subvention'],
            ['libelle' => 'Achat matériel', 'description' => 'Achat de matériel'],
            ['libelle' => 'Frais de repas', 'description' => 'Frais de repas'],
            ['libelle' => 'Carburant', 'description' => 'Carburant'],
        ];

        foreach ($typesData as $data) {
            $type = new TypeTransaction();
            $type->setLibelle($data['libelle']);
            $type->setDescription($data['description']);
            $manager->persist($type);
            $types[] = $type;
        }

        return $types;
    }

    private function createExercices(ObjectManager $manager): array
    {
        $exercices = [];
        
        // Exercice 2023 (clos)
        $exercice2023 = new Exercice();
        $exercice2023->setLibelle('Exercice 2023');
        $exercice2023->setNumeroOrdre(1);
        $exercice2023->setDateDebut(new \DateTime('2023-01-01'));
        $exercice2023->setDateFin(new \DateTime('2023-12-31'));
        $exercice2023->setClos(true);
        $manager->persist($exercice2023);
        $exercices[] = $exercice2023;

        // Exercice 2024 (en cours)
        $exercice2024 = new Exercice();
        $exercice2024->setLibelle('Exercice 2024');
        $exercice2024->setNumeroOrdre(2);
        $exercice2024->setDateDebut(new \DateTime('2024-01-01'));
        $exercice2024->setDateFin(new \DateTime('2024-12-31'));
        $exercice2024->setClos(false);
        $manager->persist($exercice2024);
        $exercices[] = $exercice2024;

        return $exercices;
    }

    private function createTransactions(ObjectManager $manager, array $exercices, array $personnes, array $types, array $modesPaiement): void
    {
        // Transactions 2023
        $transactionsData2023 = [
            ['date' => '2023-01-15', 'montant' => '50.00', 'libelle' => 'Cotisation annuelle Gautherot', 'personne' => 0, 'type' => 0, 'mode' => 1],
            ['date' => '2023-02-10', 'montant' => '25.00', 'libelle' => 'Don pour matériel Herbst', 'personne' => 1, 'type' => 1, 'mode' => 0],
            ['date' => '2023-03-20', 'montant' => '-150.00', 'libelle' => 'Achat outils', 'personne' => null, 'type' => 3, 'mode' => 1],
        ];

        $ordre = 1;
        foreach ($transactionsData2023 as $data) {
            $transaction = new Transaction();
            $transaction->setDateTransaction(new \DateTime($data['date']));
            $transaction->setMontant($data['montant']);
            $transaction->setLibelle($data['libelle']);
            $transaction->setNumeroOrdre($ordre++);
            $transaction->setExercice($exercices[0]); // 2023
            $transaction->setTypeTransaction($types[$data['type']]);
            $transaction->setModeDePaiement($modesPaiement[$data['mode']]);
            if ($data['personne'] !== null) {
                $transaction->setPersonne($personnes[$data['personne']]);
            }
            $manager->persist($transaction);
        }

        // Transactions 2024
        $transactionsData2024 = [
            ['date' => '2024-01-10', 'montant' => '50.00', 'libelle' => 'Cotisation annuelle Martin 2024', 'personne' => 2, 'type' => 0, 'mode' => 2],
            ['date' => '2024-02-05', 'montant' => '75.00', 'libelle' => 'Don généreux Obert', 'personne' => 3, 'type' => 1, 'mode' => 1],
            ['date' => '2024-03-15', 'montant' => '-80.00', 'libelle' => 'Repas équipe', 'personne' => null, 'type' => 4, 'mode' => 0],
            ['date' => '2024-04-20', 'montant' => '-45.00', 'libelle' => 'Essence véhicule', 'personne' => null, 'type' => 5, 'mode' => 3],
        ];

        $ordre = 1;
        foreach ($transactionsData2024 as $data) {
            $transaction = new Transaction();
            $transaction->setDateTransaction(new \DateTime($data['date']));
            $transaction->setMontant($data['montant']);
            $transaction->setLibelle($data['libelle']);
            $transaction->setNumeroOrdre($ordre++);
            $transaction->setExercice($exercices[1]); // 2024
            $transaction->setTypeTransaction($types[$data['type']]);
            $transaction->setModeDePaiement($modesPaiement[$data['mode']]);
            if ($data['personne'] !== null) {
                $transaction->setPersonne($personnes[$data['personne']]);
            }
            $manager->persist($transaction);
        }
    }
}