<?php

namespace App\DataFixtures;

use App\Entity\Personne;
use App\Entity\Entreprise;
use App\Entity\Exercice;
use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use App\Entity\ModeDePaiement;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RealDataFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        echo "🚀 Chargement des fixtures Amicale RCA...\n";

        // 1. Récupérer les rôles existants créés par PermissionFixtures
        $roles = $this->getRolesExistants($manager);
        
        // 2. Créer l'entreprise
        $entreprise = $this->createEntreprise($manager);
        
        // 3. Créer les personnes
        $personnes = $this->createPersonnes($manager);
        
        // 4. Créer l'utilisateur admin
        $admin = $this->createAdmin($manager, $roles, $personnes, $entreprise);
        
        // 5. Créer les modes de paiement
        $modes = $this->createModesPaiement($manager);
        
        // 6. Créer les types de transaction
        $types = $this->createTypesTransaction($manager);
        
        // 7. Créer les exercices
        $exercices = $this->createExercices($manager, $admin);
        
        // 8. Créer quelques transactions
        $this->createSampleTransactions($manager, $exercices, $personnes, $types, $modes);
        
        $manager->flush();
        echo "✅ Fixtures chargées avec succès!\n";
    }

    private function getRolesExistants(ObjectManager $manager): array
    {
        // Récupérer les rôles existants créés par PermissionFixtures
        $roleRepository = $manager->getRepository(Role::class);
        
        $roles = [];
        $roles['ROLE_ADMIN'] = $roleRepository->findOneBy(['libelle' => 'Administrateur']);
        $roles['ROLE_USER'] = $roleRepository->findOneBy(['libelle' => 'Utilisateur']);
        $roles['ROLE_GUEST'] = $roleRepository->findOneBy(['libelle' => 'Invité']);
        
        // Si les rôles n'existent pas, les créer
        if (!$roles['ROLE_ADMIN']) {
            $admin = new Role();
            $admin->setLibelle('Administrateur');
            $admin->setDescription('Administrateur système');
            $admin->setHierarchyLevel(100);
            $manager->persist($admin);
            $roles['ROLE_ADMIN'] = $admin;
        }
        
        if (!$roles['ROLE_USER']) {
            $user = new Role();
            $user->setLibelle('Utilisateur');
            $user->setDescription('Utilisateur standard');
            $user->setHierarchyLevel(50);
            $manager->persist($user);
            $roles['ROLE_USER'] = $user;
        }
        
        echo "✅ Rôles récupérés/créés\n";
        return $roles;
    }

    private function createEntreprise(ObjectManager $manager): Entreprise
    {
        $entreprise = new Entreprise();
        $entreprise->setNomEntreprise('AMICALE DES ANCIENS DU RUGBY CLUB AUDOMAROIS');
        $entreprise->setRue('ALLEE DES SPORTS');
        $entreprise->setVille('SAINT OMER');
        $entreprise->setCodePostal(62500);
        $entreprise->setPays('France');
        $manager->persist($entreprise);
        
        echo "✅ Entreprise créée\n";
        return $entreprise;
    }

    private function createPersonnes(ObjectManager $manager): array
    {
        $personnesData = [
            ['nom' => 'GAUTHEROT', 'prenom' => 'Didier', 'email' => null],
            ['nom' => 'CLAY', 'prenom' => 'Jacques', 'email' => null],
            ['nom' => 'HERBST', 'prenom' => 'David', 'email' => 'david-herbst@orange.fr'],
            ['nom' => 'BEGUET', 'prenom' => 'Pierre', 'email' => null],
            ['nom' => 'DESMARETZ', 'prenom' => 'Paul', 'email' => 'paul.demaretz@wanadoo.fr'],
            ['nom' => 'IDZIK', 'prenom' => 'Bernard', 'email' => null],
            ['nom' => 'VERBRUGGHE', 'prenom' => 'Max', 'email' => 'maxverbrugghe@gmail.com'],
            ['nom' => 'TOUTAIN', 'prenom' => 'Nicolas', 'email' => 'nicolas.toutain1@free.fr'],
            ['nom' => 'STEENKESTE', 'prenom' => 'Cédric', 'email' => 'cedric.steenkeste@tereos.com'],
            ['nom' => 'FASQUELLE', 'prenom' => 'Yves', 'email' => 'yves.fasquelle@neuf.fr'],
            // Admin
            ['nom' => 'ADMIN', 'prenom' => 'Admin', 'email' => 'admin@rca-amicale.fr'],
        ];

        $personnes = [];
        foreach ($personnesData as $data) {
            $personne = new Personne();
            $personne->setNom($data['nom']);
            $personne->setPrenom($data['prenom']);
            $personne->setCivilite('Mr');
            if ($data['email']) {
                $personne->setEmail($data['email']);
            }
            $personne->setPays('France');
            $manager->persist($personne);
            $personnes[] = $personne;
        }
        
        echo "✅ Personnes créées\n";
        return $personnes;
    }

    private function createAdmin(ObjectManager $manager, array $roles, array $personnes, Entreprise $entreprise): User
    {
        // La dernière personne est l'admin
        $adminPersonne = end($personnes);
        
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setPersonne($adminPersonne);
        
        // Mot de passe Test123
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Test123');
        $admin->setPassword($hashedPassword);
        
        // Ajouter le rôle admin principal
        if (isset($roles['ROLE_ADMIN'])) {
            $admin->addRole($roles['ROLE_ADMIN']);
        }
        if (isset($roles['ROLE_USER'])) {
            $admin->addRole($roles['ROLE_USER']);
        }
        
        // Associer à l'entreprise
        $adminPersonne->addEntreprise($entreprise);
        
        $manager->persist($admin);
        
        echo "✅ Utilisateur admin créé (login: admin, mot de passe: Test123)\n";
        return $admin;
    }

    private function createModesPaiement(ObjectManager $manager): array
    {
        $modesData = [
            'Espèces' => 'Paiement en espèces',
            'Chèque' => 'Paiement par chèque',
            'Virement' => 'Paiement par virement bancaire',
            'Carte bancaire' => 'Paiement par carte bancaire'
        ];

        $modes = [];
        foreach ($modesData as $libelle => $description) {
            $mode = new ModeDePaiement();
            $mode->setLibelle($libelle);
            // La description existe-t-elle dans l'entité ModeDePaiement ? Vérifions d'abord
            $manager->persist($mode);
            $modes[$libelle] = $mode;
        }
        
        echo "✅ Modes de paiement créés\n";
        return $modes;
    }

    private function createTypesTransaction(ObjectManager $manager): array
    {
        $typesData = [
            'Cotisation' => ['description' => 'Cotisation annuelle', 'nature' => 'recette'],
            'Achat repas' => ['description' => 'Achat nourriture', 'nature' => 'depense'],
            'Participation école de rugby' => ['description' => 'Participation club', 'nature' => 'depense'],
            'Report à nouveau' => ['description' => 'Report solde', 'nature' => 'recette'],
            'Tenue de compte' => ['description' => 'Frais bancaires', 'nature' => 'depense']
        ];

        $types = [];
        foreach ($typesData as $libelle => $data) {
            $type = new TypeTransaction();
            $type->setLibelle($libelle);
            $type->setDescription($data['description']);
            // setNature n'existe pas, on utilise setTypeMontantAutorise
            if ($data['nature'] === 'recette') {
                $type->setTypeMontantAutorise('credit');
            } else {
                $type->setTypeMontantAutorise('debit');
            }
            $manager->persist($type);
            $types[$libelle] = $type;
        }
        
        echo "✅ Types de transaction créés\n";
        return $types;
    }

    private function createExercices(ObjectManager $manager, User $admin): array
    {
        $exercicesData = [
            [
                'libelle' => 'Exercice 2023-2024',
                'debut' => '2023-10-01',
                'fin' => '2024-08-31',
                'solde' => '3225.89',
                'cloture' => true
            ],
            [
                'libelle' => 'Exercice 2024-2025',
                'debut' => '2024-09-01',
                'fin' => '2025-08-31',
                'solde' => '4357.34',
                'cloture' => false
            ]
        ];

        $exercices = [];
        $ordre = 1;
        foreach ($exercicesData as $data) {
            $exercice = new Exercice();
            $exercice->setLibelle($data['libelle']);
            $exercice->setDateDebut(new \DateTime($data['debut']));
            $exercice->setDateFin(new \DateTime($data['fin']));
            $exercice->setNumeroOrdre($ordre++);
            $exercice->setClos($data['cloture']);
            $manager->persist($exercice);
            $exercices[] = $exercice;
        }
        
        echo "✅ Exercices créés\n";
        return $exercices;
    }

    private function createSampleTransactions(ObjectManager $manager, array $exercices, array $personnes, array $types, array $modes): void
    {
        $exerciceActuel = $exercices[1]; // 2024-2025
        
        // Report à nouveau
        $transaction = new Transaction();
        $transaction->setLibelle('Report à nouveau exercice 2024-2025');
        $transaction->setMontant('4357.34');
        $transaction->setNumeroOrdre(1);
        $transaction->setDateTransaction(new \DateTime('2024-09-01'));
        $transaction->setExercice($exerciceActuel);
        $transaction->setTypeCompte('compte_courant');
        $transaction->setTypeTransaction($types['Report à nouveau']);
        $transaction->setModeDePaiement($modes['Virement']);
        $manager->persist($transaction);

        // Quelques cotisations
        $cotisations = [
            ['personne' => $personnes[0], 'montant' => '30.00', 'date' => '2024-12-06'], // GAUTHEROT
            ['personne' => $personnes[2], 'montant' => '100.00', 'date' => '2024-11-25'], // HERBST
            ['personne' => $personnes[7], 'montant' => '100.00', 'date' => '2024-11-25'], // TOUTAIN
            ['personne' => $personnes[8], 'montant' => '300.00', 'date' => '2024-12-31'], // STEENKESTE
        ];

        $ordre = 2;
        foreach ($cotisations as $cot) {
            $transaction = new Transaction();
            $transaction->setLibelle('Cotisation ' . $cot['personne']->getPrenom() . ' ' . $cot['personne']->getNom());
            $transaction->setMontant($cot['montant']);
            $transaction->setNumeroOrdre($ordre++);
            $transaction->setDateTransaction(new \DateTime($cot['date']));
            $transaction->setExercice($exerciceActuel);
            $transaction->setTypeCompte('compte_courant');
            $transaction->setTypeTransaction($types['Cotisation']);
            $transaction->setModeDePaiement($modes['Virement']);
            $transaction->setPersonne($cot['personne']);
            $manager->persist($transaction);
        }

        // Quelques dépenses
        $depenses = [
            ['libelle' => 'Participation école de rugby', 'montant' => '-607.60', 'date' => '2024-09-12'],
            ['libelle' => 'Repas amicale Marco traiteur', 'montant' => '-1233.69', 'date' => '2024-11-25'],
        ];

        foreach ($depenses as $dep) {
            $transaction = new Transaction();
            $transaction->setLibelle($dep['libelle']);
            $transaction->setMontant($dep['montant']);
            $transaction->setNumeroOrdre($ordre++);
            $transaction->setDateTransaction(new \DateTime($dep['date']));
            $transaction->setExercice($exerciceActuel);
            $transaction->setTypeCompte('compte_courant');
            
            if (strpos($dep['libelle'], 'école de rugby') !== false) {
                $transaction->setTypeTransaction($types['Participation école de rugby']);
            } else {
                $transaction->setTypeTransaction($types['Achat repas']);
            }
            
            $transaction->setModeDePaiement($modes['Chèque']);
            $manager->persist($transaction);
        }
        
        echo "✅ Transactions d'exemple créées\n";
    }
}