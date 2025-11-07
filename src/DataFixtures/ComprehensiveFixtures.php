<?php<?php<?php



namespace App\DataFixtures;



use App\Entity\Personne;namespace App\DataFixtures;namespace App\DataFixtures;

use App\Entity\Entreprise;

use App\Entity\Exercice;

use App\Entity\Transaction;

use App\Entity\TypeTransaction;use App\Entity\Personne;use App\Entity\Personne;

use App\Entity\ModeDePaiement;

use App\Entity\HistoriqueCloture;use App\Entity\Entreprise;use App\Entity\Entreprise;

use App\Entity\User;

use App\Entity\Role;use App\Entity\Exercice;use App\Entity\Exercice;

use Doctrine\Bundle\FixturesBundle\Fixture;

use Doctrine\Persistence\ObjectManager;use App\Entity\Transaction;use App\Entity\Transaction;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\TypeTransaction;use App\Entity\TypeTransaction;

class ComprehensiveFixtures extends Fixture

{use App\Entity\ModeDePaiement;use App\Entity\ModeDePaiement;

    private UserPasswordHasherInterface $passwordHasher;

use App\Entity\HistoriqueCloture;use App\Entity\HistoriqueCloture;

    public function __construct(UserPasswordHasherInterface $passwordHasher)

    {use App\Entity\User;use App\Entity\User;

        $this->passwordHasher = $passwordHasher;

    }use App\Entity\Role;use App\Entity\Role;



    public function load(ObjectManager $manager): voiduse Doctrine\Bundle\FixturesBundle\Fixture;use Doctrine\Bundle\FixturesBundle\Fixture;

    {

        echo "🚀 Chargement des fixtures de l'Amicale RCA...\n";use Doctrine\Persistence\ObjectManager;use Doctrine\Persistence\ObjectManager;



        // ========== RÔLES ==========use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

        $roles = $this->createRoles($manager);

        echo "✅ Rôles créés\n";



        // ========== ENTREPRISE ==========class ComprehensiveFixtures extends Fixtureclass ComprehensiveFixtures extends Fixture

        $entreprise = $this->createEntreprise($manager);

        echo "✅ Entreprise créée\n";{{



        // ========== PERSONNES ==========    private UserPasswordHasherInterface $passwordHasher;    private UserPasswordHasherInterface $passwordHasher;

        $personnes = $this->createPersonnes($manager);

        echo "✅ Personnes créées\n";



        // ========== UTILISATEURS ==========    public function __construct(UserPasswordHasherInterface $passwordHasher)    public function __construct(UserPasswordHasherInterface $passwordHasher)

        $users = $this->createUsers($manager, $roles, $personnes, $entreprise);

        echo "✅ Utilisateurs créés\n";    {    {



        // ========== MODES DE PAIEMENT ==========        $this->passwordHasher = $passwordHasher;        $this->passwordHasher = $passwordHasher;

        $modes = $this->createModesPaiement($manager);

        echo "✅ Modes de paiement créés\n";    }    }



        // ========== TYPES DE TRANSACTION ==========

        $types = $this->createTypesTransaction($manager);

        echo "✅ Types de transaction créés\n";    public function load(ObjectManager $manager): void    public function load(ObjectManager $manager): void



        // ========== EXERCICES ==========    {    {

        $exercices = $this->createExercices($manager, $users[0]); // admin user

        echo "✅ Exercices créés\n";        echo "🚀 Chargement des fixtures de l'Amicale RCA...\n";        echo "🚀 Chargement des fixtures complètes...\n";



        // ========== TRANSACTIONS ==========

        $this->createTransactions($manager, $exercices, $personnes, $types, $modes);

        echo "✅ Transactions créées\n";        // ========== RÔLES ==========        // ========== RÔLES ==========



        $manager->flush();        $roles = $this->createRoles($manager);        $roles = $this->createRoles($manager);

        echo "🎉 Fixtures chargées avec succès !\n";

    }        echo "✅ Rôles créés\n";        echo "✅ Rôles créés\n";



    private function createRoles(ObjectManager $manager): array

    {

        $roles = [        // ========== ENTREPRISE ==========        // ========== PERSONNES (D'ABORD) ==========

            ['nom' => 'ROLE_USER', 'label' => 'Utilisateur'],

            ['nom' => 'ROLE_ADMIN', 'label' => 'Administrateur'],        $entreprise = $this->createEntreprise($manager);        $personnes = $this->createPersonnes($manager);

            ['nom' => 'ROLE_SUPER_ADMIN', 'label' => 'Super Administrateur'],

            ['nom' => 'ROLE_TREASURER', 'label' => 'Trésorier'],        echo "✅ Entreprise créée\n";        echo "✅ Personnes créées\n";

        ];



        $rolesEntities = [];

        foreach ($roles as $roleData) {        // ========== PERSONNES ==========        // ========== UTILISATEURS ==========

            $role = new Role();

            $role->setNom($roleData['nom']);        $personnes = $this->createPersonnes($manager);        $users = $this->createUsers($manager, $roles, $personnes);

            $role->setLabel($roleData['label']);

            $manager->persist($role);        echo "✅ Personnes créées\n";        echo "✅ Utilisateurs créés\n";

            $rolesEntities[] = $role;

        }



        return $rolesEntities;        // ========== UTILISATEURS ==========        // ========== EXERCICES ==========

    }

        $users = $this->createUsers($manager, $roles, $personnes, $entreprise);        $exercices = $this->createExercices($manager, $users);

    private function createEntreprise(ObjectManager $manager): Entreprise

    {        echo "✅ Utilisateurs créés\n";        echo "✅ Exercices créés\n";

        $entreprise = new Entreprise();

        $entreprise->setNom('AMICALE DES ANCIENS DU RUGBY CLUB AUDOMAROIS');

        $entreprise->setAdresse('ALLEE DES SPORTS');

        $entreprise->setVille('SAINT OMER');        // ========== MODES DE PAIEMENT ==========        // ========== MODES DE PAIEMENT ==========

        $entreprise->setCodePostal(62500);

        $entreprise->setPays('France');        $modes = $this->createModesPaiement($manager);        $modes = $this->createModesPaiement($manager);

        $manager->persist($entreprise);

        echo "✅ Modes de paiement créés\n";        echo "✅ Modes de paiement créés\n";

        return $entreprise;

    }



    private function createPersonnes(ObjectManager $manager): array        // ========== TYPES DE TRANSACTION ==========        // ========== TYPES DE TRANSACTIONS ==========

    {

        $personnesData = [        $types = $this->createTypesTransaction($manager);        $types = $this->createTypesTransaction($manager);

            ['nom' => 'GAUTHEROT', 'prenom' => 'Didier', 'civilite' => 'Mr', 'rue' => '188, rue de Dunkerque', 'ville' => 'St Omer', 'code_postal' => 62500, 'email' => null],

            ['nom' => 'CLAY', 'prenom' => 'Jacques', 'civilite' => 'Mr', 'rue' => '83C, route de St Momelin', 'ville' => 'Nieurlet', 'code_postal' => 59143, 'email' => null],        echo "✅ Types de transaction créés\n";        echo "✅ Types de transactions créés\n";

            ['nom' => 'HERBST', 'prenom' => 'David', 'civilite' => 'Mr', 'rue' => '9 rue de la Chapelle', 'ville' => 'Tilques', 'code_postal' => 62500, 'email' => 'david-herbst@orange.fr'],

            ['nom' => 'DELABY', 'prenom' => 'Fabienne', 'civilite' => 'Mme', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],

            ['nom' => 'BEGUET', 'prenom' => 'Pierre', 'civilite' => 'Mr', 'rue' => '29, route de Sermoise', 'ville' => 'Sermoise sur Loire', 'code_postal' => 58000, 'email' => null],

            ['nom' => 'CHOUCHANA', 'prenom' => 'Hervé', 'civilite' => 'Mr', 'rue' => '43, Avenue du Val saint André', 'ville' => 'Aix en Provence', 'code_postal' => 13100, 'email' => null],        // ========== EXERCICES ==========        // ========== ENTREPRISES ==========

            ['nom' => 'DESMARETZ', 'prenom' => 'Paul', 'civilite' => 'Mr', 'rue' => '20 rue Pasteur, St martin au L', 'ville' => 'St Martin lez Tatinghem', 'code_postal' => 62500, 'email' => 'paul.demaretz@wanadoo.fr'],

            ['nom' => 'IDZIK', 'prenom' => 'Bernard', 'civilite' => 'Mr', 'rue' => '34 rue de la pierre', 'ville' => 'Racquinghem', 'code_postal' => 62120, 'email' => null],        $exercices = $this->createExercices($manager, $users[0]); // admin user        $entreprises = $this->createEntreprises($manager);

            ['nom' => 'IDZIK', 'prenom' => 'Bertrand', 'civilite' => 'Mr', 'rue' => '6 rue de leuline', 'ville' => 'LEULINGHEM', 'code_postal' => 62500, 'email' => null],

            ['nom' => 'VERBRUGGHE', 'prenom' => 'Max', 'civilite' => 'Mr', 'rue' => '2, rue Roger salengro', 'ville' => 'Noeux les Mines', 'code_postal' => 62290, 'email' => 'maxverbrugghe@gmail.com'],        echo "✅ Exercices créés\n";        echo "✅ Entreprises créées\n";

            ['nom' => 'MARTIN', 'prenom' => 'Louis', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],

            ['nom' => 'CANLER', 'prenom' => 'Marc Antoine', 'civilite' => 'Mr', 'rue' => '430 rue de Bailleul', 'ville' => 'MERRIS', 'code_postal' => 59270, 'email' => 'marcantoine@filrouge-expertscomptables.com'],

            ['nom' => 'DELABY', 'prenom' => 'Pierre', 'civilite' => 'Mr', 'rue' => '15, rue du 11 novembre', 'ville' => 'GUARBECQUE', 'code_postal' => 62330, 'email' => 'pierre,delaby62@gmail.com'],

            ['nom' => 'VERDRU', 'prenom' => 'JC', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],        // ========== TRANSACTIONS ==========        // ========== TRANSACTIONS ==========

            ['nom' => 'BRUGES', 'prenom' => 'Walter', 'civilite' => 'Mr', 'rue' => '60, route de Thérouanne', 'ville' => 'PIHEM', 'code_postal' => 62570, 'email' => 'walter.bruges@orange.fr'],

            ['nom' => 'FARDOUX', 'prenom' => 'Gilles', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],        $this->createTransactions($manager, $exercices, $personnes, $types, $modes);        $this->createTransactions($manager, $exercices, $types, $modes, $personnes, $entreprises);

            ['nom' => 'WALLOIS', 'prenom' => 'Jérôme', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],

            ['nom' => 'FASQUELLE', 'prenom' => 'Yves', 'civilite' => 'Mr', 'rue' => '163 A, Bd Georges Pompidou', 'ville' => 'Bray-Dunes', 'code_postal' => 59123, 'email' => 'yves.fasquelle@neuf.fr'],        echo "✅ Transactions créées\n";        echo "✅ Transactions créées\n";

            ['nom' => 'OBERT', 'prenom' => 'Armand', 'civilite' => 'Mr', 'rue' => '1, rue des prés fleuris', 'ville' => 'WITTES', 'code_postal' => 62120, 'email' => 'armand.obert@yahoo.fr'],

            ['nom' => 'BRUGES', 'prenom' => 'Jean-Marie', 'civilite' => 'Mr', 'rue' => '570, rue de l\'Argilière', 'ville' => 'Helfaut', 'code_postal' => 62570, 'email' => 'jmbruges@nordnet.fr'],

            ['nom' => 'BOULINGUIEZ', 'prenom' => 'Jean-Paul', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => 'Jeanpaul.boulinguiez@nordnet.fr'],

            ['nom' => 'TOUTAIN', 'prenom' => 'Nicolas', 'civilite' => 'Mr', 'rue' => '7, Impasse la Belle Etoile', 'ville' => 'La Montagne', 'code_postal' => 44620, 'email' => 'nicolas.toutain1@free.fr'],        $manager->flush();        // ========== HISTORIQUES DE CLÔTURE ==========

            ['nom' => 'STEENKESTE', 'prenom' => 'Cédric', 'civilite' => 'Mr', 'rue' => '23, rue Louis Flamant', 'ville' => 'Gauchy', 'code_postal' => 2430, 'email' => 'cedric.steenkeste@tereos.com'],

            ['nom' => 'GUILBERT', 'prenom' => 'Kévin', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => 'kevinguilbert62@gmail.com'],        echo "🎉 Fixtures chargées avec succès !\n";        $this->createHistoriquesCloture($manager, $exercices, $users);

            ['nom' => 'ROUSSEL', 'prenom' => 'Etienne', 'civilite' => 'Mr', 'rue' => '2 domaine des Genets', 'ville' => 'LONGUENESSE', 'code_postal' => 62219, 'email' => 'monique.etienne-roussel@wanadoo.fr'],

            ['nom' => 'BLONDE', 'prenom' => 'Guy', 'civilite' => 'Mr', 'rue' => '7, rue du marais', 'ville' => 'Moulle', 'code_postal' => 62910, 'email' => 'guy.blonde@free.fr'],    }        echo "✅ Historiques de clôture créés\n";

            // Utilisateur admin

            ['nom' => 'ADMIN', 'prenom' => 'Admin', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => 'admin@rca-amicale.fr'],

        ];

    private function createRoles(ObjectManager $manager): array        $manager->flush();

        $personnes = [];

        foreach ($personnesData as $data) {    {        echo "🎉 Fixtures chargées avec succès!\n";

            $personne = new Personne();

            $personne->setNom($data['nom']);        $roles = [    }

            $personne->setPrenom($data['prenom']);

            $personne->setCivilite($data['civilite']);            ['nom' => 'ROLE_USER', 'label' => 'Utilisateur'],

            if ($data['rue']) $personne->setRue($data['rue']);

            if ($data['ville']) $personne->setVille($data['ville']);            ['nom' => 'ROLE_ADMIN', 'label' => 'Administrateur'],    private function createRoles(ObjectManager $manager): array

            if ($data['code_postal']) $personne->setCodePostal($data['code_postal']);

            if ($data['email']) $personne->setEmail($data['email']);            ['nom' => 'ROLE_SUPER_ADMIN', 'label' => 'Super Administrateur'],    {

            $personne->setPays('France');

                        ['nom' => 'ROLE_TREASURER', 'label' => 'Trésorier'],        $roles = [];

            $manager->persist($personne);

            $personnes[] = $personne;        ];

        }

        $roleAdmin = new Role();

        return $personnes;

    }        $rolesEntities = [];        $roleAdmin->setLibelle('ROLE_ADMIN');



    private function createUsers(ObjectManager $manager, array $roles, array $personnes, Entreprise $entreprise): array        foreach ($roles as $roleData) {        $roleAdmin->setDescription('Administrateur système');

    {

        // Créer l'utilisateur admin            $role = new Role();        $manager->persist($roleAdmin);

        $adminPersonne = end($personnes); // La dernière personne est l'admin

        $adminUser = new User();            $role->setNom($roleData['nom']);        $roles['admin'] = $roleAdmin;

        $adminUser->setUsername('admin');

        $adminUser->setPersonne($adminPersonne);            $role->setLabel($roleData['label']);

        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'Test123');

        $adminUser->setPassword($hashedPassword);            $manager->persist($role);        $roleUser = new Role();

        

        // Ajouter tous les rôles à l'admin            $rolesEntities[] = $role;        $roleUser->setLibelle('ROLE_USER');

        foreach ($roles as $role) {

            $adminUser->addRole($role);        }        $roleUser->setDescription('Utilisateur standard');

        }

                $manager->persist($roleUser);

        // Associer l'admin à l'entreprise

        $adminPersonne->addEntreprise($entreprise);        return $rolesEntities;        $roles['user'] = $roleUser;

        

        $manager->persist($adminUser);    }



        // Créer quelques autres utilisateurs avec le rôle USER        return $roles;

        $users = [$adminUser];

            private function createEntreprise(ObjectManager $manager): Entreprise    }

        // Créer un utilisateur trésorier (David Herbst)

        $treasurerPersonne = $personnes[2]; // David HERBST    {

        $treasurerUser = new User();

        $treasurerUser->setUsername('david.herbst');        $entreprise = new Entreprise();    private function createUsers(ObjectManager $manager, array $roles, array $personnes): array

        $treasurerUser->setPersonne($treasurerPersonne);

        $hashedPassword = $this->passwordHasher->hashPassword($treasurerUser, 'Test123');        $entreprise->setNom('AMICALE DES ANCIENS DU RUGBY CLUB AUDOMAROIS');    {

        $treasurerUser->setPassword($hashedPassword);

        $treasurerUser->addRole($roles[0]); // ROLE_USER        $entreprise->setAdresse('ALLEE DES SPORTS');        $users = [];

        $treasurerUser->addRole($roles[3]); // ROLE_TREASURER

        $treasurerPersonne->addEntreprise($entreprise);        $entreprise->setVille('SAINT OMER');

        

        $manager->persist($treasurerUser);        $entreprise->setCodePostal(62500);        // Admin

        $users[] = $treasurerUser;

        $entreprise->setPays('France');        $admin = new User();

        return $users;

    }        $manager->persist($entreprise);        $admin->setUsername('admin');



    private function createModesPaiement(ObjectManager $manager): array        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));

    {

        $modesData = [        return $entreprise;        $admin->setPersonne($personnes['dupont']);

            ['libelle' => 'Espèces', 'description' => 'Paiement en espèces'],

            ['libelle' => 'Chèque', 'description' => 'Paiement par chèque'],    }        $admin->addRole($roles['admin']);

            ['libelle' => 'Virement', 'description' => 'Paiement par virement bancaire'],

            ['libelle' => 'Carte bancaire', 'description' => 'Paiement par carte bancaire'],        $admin->addRole($roles['user']);

            ['libelle' => 'Prélèvement', 'description' => 'Prélèvement automatique'],

        ];    private function createPersonnes(ObjectManager $manager): array        $manager->persist($admin);



        $modes = [];    {        $users['admin'] = $admin;

        foreach ($modesData as $data) {

            $mode = new ModeDePaiement();        $personnesData = [

            $mode->setLibelle($data['libelle']);

            $mode->setDescription($data['description']);            ['nom' => 'GAUTHEROT', 'prenom' => 'Didier', 'civilite' => 'Mr', 'rue' => '188, rue de Dunkerque', 'ville' => 'St Omer', 'code_postal' => 62500, 'email' => null],        // Trésorier

            $manager->persist($mode);

            $modes[] = $mode;            ['nom' => 'CLAY', 'prenom' => 'Jacques', 'civilite' => 'Mr', 'rue' => '83C, route de St Momelin', 'ville' => 'Nieurlet', 'code_postal' => 59143, 'email' => null],        $tresorier = new User();

        }

            ['nom' => 'HERBST', 'prenom' => 'David', 'civilite' => 'Mr', 'rue' => '9 rue de la Chapelle', 'ville' => 'Tilques', 'code_postal' => 62500, 'email' => 'david-herbst@orange.fr'],        $tresorier->setUsername('tresorier');

        return $modes;

    }            ['nom' => 'DELABY', 'prenom' => 'Fabienne', 'civilite' => 'Mme', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],        $tresorier->setPassword($this->passwordHasher->hashPassword($tresorier, 'tresorier123'));



    private function createTypesTransaction(ObjectManager $manager): array            ['nom' => 'BEGUET', 'prenom' => 'Pierre', 'civilite' => 'Mr', 'rue' => '29, route de Sermoise', 'ville' => 'Sermoise sur Loire', 'code_postal' => 58000, 'email' => null],        $tresorier->setPersonne($personnes['martin']);

    {

        $typesData = [            ['nom' => 'CHOUCHANA', 'prenom' => 'Hervé', 'civilite' => 'Mr', 'rue' => '43, Avenue du Val saint André', 'ville' => 'Aix en Provence', 'code_postal' => 13100, 'email' => null],        $tresorier->addRole($roles['user']);

            ['libelle' => 'Cotisation', 'description' => 'Cotisation annuelle des membres', 'nature' => 'recette'],

            ['libelle' => 'Repas amicale', 'description' => 'Paiement repas amicale', 'nature' => 'recette'],            ['nom' => 'DESMARETZ', 'prenom' => 'Paul', 'civilite' => 'Mr', 'rue' => '20 rue Pasteur, St martin au L', 'ville' => 'St Martin lez Tatinghem', 'code_postal' => 62500, 'email' => 'paul.demaretz@wanadoo.fr'],        $manager->persist($tresorier);

            ['libelle' => 'Achat repas', 'description' => 'Achat nourriture pour repas', 'nature' => 'depense'],

            ['libelle' => 'Achat vaisselle', 'description' => 'Achat de vaisselle', 'nature' => 'depense'],            ['nom' => 'IDZIK', 'prenom' => 'Bernard', 'civilite' => 'Mr', 'rue' => '34 rue de la pierre', 'ville' => 'Racquinghem', 'code_postal' => 62120, 'email' => null],        $users['tresorier'] = $tresorier;

            ['libelle' => 'Achat fleurs', 'description' => 'Achat de fleurs', 'nature' => 'depense'],

            ['libelle' => 'Tenue de compte', 'description' => 'Frais bancaires', 'nature' => 'depense'],            ['nom' => 'IDZIK', 'prenom' => 'Bertrand', 'civilite' => 'Mr', 'rue' => '6 rue de leuline', 'ville' => 'LEULINGHEM', 'code_postal' => 62500, 'email' => null],

            ['libelle' => 'Ristourne', 'description' => 'Ristourne bancaire', 'nature' => 'recette'],

            ['libelle' => 'Cadeaux', 'description' => 'Achat de cadeaux', 'nature' => 'depense'],            ['nom' => 'VERBRUGGHE', 'prenom' => 'Max', 'civilite' => 'Mr', 'rue' => '2, rue Roger salengro', 'ville' => 'Noeux les Mines', 'code_postal' => 62290, 'email' => 'maxverbrugghe@gmail.com'],        // Secrétaire

            ['libelle' => 'Boisson', 'description' => 'Achat de boissons', 'nature' => 'depense'],

            ['libelle' => 'Participation école de rugby', 'description' => 'Participation aux activités du club', 'nature' => 'depense'],            ['nom' => 'MARTIN', 'prenom' => 'Louis', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],        $secretaire = new User();

            ['libelle' => 'Report à nouveau', 'description' => 'Report de solde d\'exercice', 'nature' => 'recette'],

            ['libelle' => 'Virement livret', 'description' => 'Virement vers/depuis livret d\'épargne', 'nature' => 'neutre'],            ['nom' => 'CANLER', 'prenom' => 'Marc Antoine', 'civilite' => 'Mr', 'rue' => '430 rue de Bailleul', 'ville' => 'MERRIS', 'code_postal' => 59270, 'email' => 'marcantoine@filrouge-expertscomptables.com'],        $secretaire->setUsername('secretaire');

        ];

            ['nom' => 'DELABY', 'prenom' => 'Pierre', 'civilite' => 'Mr', 'rue' => '15, rue du 11 novembre', 'ville' => 'GUARBECQUE', 'code_postal' => 62330, 'email' => 'pierre,delaby62@gmail.com'],        $secretaire->setPassword($this->passwordHasher->hashPassword($secretaire, 'secretaire123'));

        $types = [];

        foreach ($typesData as $data) {            ['nom' => 'VERDRU', 'prenom' => 'JC', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],        $secretaire->setPersonne($personnes['dubois']);

            $type = new TypeTransaction();

            $type->setLibelle($data['libelle']);            ['nom' => 'BRUGES', 'prenom' => 'Walter', 'civilite' => 'Mr', 'rue' => '60, route de Thérouanne', 'ville' => 'PIHEM', 'code_postal' => 62570, 'email' => 'walter.bruges@orange.fr'],        $secretaire->addRole($roles['user']);

            $type->setDescription($data['description']);

            $type->setNature($data['nature']);            ['nom' => 'FARDOUX', 'prenom' => 'Gilles', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],        $manager->persist($secretaire);

            $manager->persist($type);

            $types[] = $type;            ['nom' => 'WALLOIS', 'prenom' => 'Jérôme', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => null],        $users['secretaire'] = $secretaire;

        }

            ['nom' => 'FASQUELLE', 'prenom' => 'Yves', 'civilite' => 'Mr', 'rue' => '163 A, Bd Georges Pompidou', 'ville' => 'Bray-Dunes', 'code_postal' => 59123, 'email' => 'yves.fasquelle@neuf.fr'],

        return $types;

    }            ['nom' => 'OBERT', 'prenom' => 'Armand', 'civilite' => 'Mr', 'rue' => '1, rue des prés fleuris', 'ville' => 'WITTES', 'code_postal' => 62120, 'email' => 'armand.obert@yahoo.fr'],        return $users;



    private function createExercices(ObjectManager $manager, User $user): array            ['nom' => 'BRUGES', 'prenom' => 'Jean-Marie', 'civilite' => 'Mr', 'rue' => '570, rue de l\'Argilière', 'ville' => 'Helfaut', 'code_postal' => 62570, 'email' => 'jmbruges@nordnet.fr'],    }

    {

        $exercicesData = [            ['nom' => 'BOULINGUIEZ', 'prenom' => 'Jean-Paul', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => 'Jeanpaul.boulinguiez@nordnet.fr'],

            [

                'libelle' => 'Exercice 2022-2023',            ['nom' => 'TOUTAIN', 'prenom' => 'Nicolas', 'civilite' => 'Mr', 'rue' => '7, Impasse la Belle Etoile', 'ville' => 'La Montagne', 'code_postal' => 44620, 'email' => 'nicolas.toutain1@free.fr'],    private function createExercices(ObjectManager $manager, array $users): array

                'date_debut' => new \DateTime('2022-09-01'),

                'date_fin' => new \DateTime('2023-08-31'),            ['nom' => 'STEENKESTE', 'prenom' => 'Cédric', 'civilite' => 'Mr', 'rue' => '23, rue Louis Flamant', 'ville' => 'Gauchy', 'code_postal' => 2430, 'email' => 'cedric.steenkeste@tereos.com'],    {

                'solde_initial' => '0.00',

                'est_cloture' => true,            ['nom' => 'GUILBERT', 'prenom' => 'Kévin', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => 'kevinguilbert62@gmail.com'],        $exercices = [];

            ],

            [            ['nom' => 'ROUSSEL', 'prenom' => 'Etienne', 'civilite' => 'Mr', 'rue' => '2 domaine des Genets', 'ville' => 'LONGUENESSE', 'code_postal' => 62219, 'email' => 'monique.etienne-roussel@wanadoo.fr'],

                'libelle' => 'Exercice 2023-2024',

                'date_debut' => new \DateTime('2023-10-01'),            ['nom' => 'BLONDE', 'prenom' => 'Guy', 'civilite' => 'Mr', 'rue' => '7, rue du marais', 'ville' => 'Moulle', 'code_postal' => 62910, 'email' => 'guy.blonde@free.fr'],        // Exercice 2023 (clôturé)

                'date_fin' => new \DateTime('2024-08-31'),

                'solde_initial' => '3225.89',            // Utilisateur admin        $ex2023 = new Exercice();

                'est_cloture' => true,

            ],            ['nom' => 'ADMIN', 'prenom' => 'Admin', 'civilite' => 'Mr', 'rue' => null, 'ville' => null, 'code_postal' => null, 'email' => 'admin@rca-amicale.fr'],        $ex2023->setLibelle('Exercice Comptable 2023');

            [

                'libelle' => 'Exercice 2024-2025',        ];        $ex2023->setDateDebut(new \DateTime('2023-01-01'));

                'date_debut' => new \DateTime('2024-09-01'),

                'date_fin' => new \DateTime('2025-08-31'),        $ex2023->setDateFin(new \DateTime('2023-12-31'));

                'solde_initial' => '4357.34',

                'est_cloture' => false,        $personnes = [];        $ex2023->setNumeroOrdre(1);

            ],

        ];        foreach ($personnesData as $data) {        $ex2023->setClos(true);



        $exercices = [];            $personne = new Personne();        $manager->persist($ex2023);

        foreach ($exercicesData as $data) {

            $exercice = new Exercice();            $personne->setNom($data['nom']);        $exercices['2023'] = $ex2023;

            $exercice->setLibelle($data['libelle']);

            $exercice->setDateDebut($data['date_debut']);            $personne->setPrenom($data['prenom']);

            $exercice->setDateFin($data['date_fin']);

            $exercice->setSoldeInitial($data['solde_initial']);            $personne->setCivilite($data['civilite']);        // Exercice 2024 (clôturé)

            $exercice->setEstCloture($data['est_cloture']);

            $exercice->setCreePar($user);            if ($data['rue']) $personne->setRue($data['rue']);        $ex2024 = new Exercice();

            $exercice->setDateCreation(new \DateTime());

                        if ($data['ville']) $personne->setVille($data['ville']);        $ex2024->setLibelle('Exercice Comptable 2024');

            $manager->persist($exercice);

            $exercices[] = $exercice;            if ($data['code_postal']) $personne->setCodePostal($data['code_postal']);        $ex2024->setDateDebut(new \DateTime('2024-01-01'));

        }

            if ($data['email']) $personne->setEmail($data['email']);        $ex2024->setDateFin(new \DateTime('2024-12-31'));

        return $exercices;

    }            $personne->setPays('France');        $ex2024->setNumeroOrdre(2);



    private function createTransactions(ObjectManager $manager, array $exercices, array $personnes, array $types, array $modes): void                    $ex2024->setClos(true);

    {

        // Index des types de transaction par libellé            $manager->persist($personne);        $manager->persist($ex2024);

        $typesByLibelle = [];

        foreach ($types as $type) {            $personnes[] = $personne;        $exercices['2024'] = $ex2024;

            $typesByLibelle[$type->getLibelle()] = $type;

        }        }



        // Index des modes de paiement par libellé        // Exercice 2025 (en cours)

        $modesByLibelle = [];

        foreach ($modes as $mode) {        return $personnes;        $ex2025 = new Exercice();

            $modesByLibelle[$mode->getLibelle()] = $mode;

        }    }        $ex2025->setLibelle('Exercice Comptable 2025');



        // Index des personnes par nom/prénom        $ex2025->setDateDebut(new \DateTime('2025-01-01'));

        $personnesByName = [];

        foreach ($personnes as $personne) {    private function createUsers(ObjectManager $manager, array $roles, array $personnes, Entreprise $entreprise): array        $ex2025->setDateFin(new \DateTime('2025-12-31'));

            $key = strtolower($personne->getNom() . '_' . $personne->getPrenom());

            $personnesByName[$key] = $personne;    {        $ex2025->setNumeroOrdre(3);

        }

        // Créer l'utilisateur admin        $ex2025->setClos(false);

        // Exercice 2023-2024 (quelques transactions principales)

        $exercice2023 = $exercices[1];        $adminPersonne = end($personnes); // La dernière personne est l'admin        $manager->persist($ex2025);

        $transactions2023 = [

            [        $adminUser = new User();        $exercices['2025'] = $ex2025;

                'date' => '2024-01-10',

                'libelle' => 'Report à nouveau exercice 2023-2024',        $adminUser->setUsername('admin');

                'montant' => '3225.89',

                'numero_ordre' => 1,        $adminUser->setPersonne($adminPersonne);        // Exercice 2026 (futur)

                'type' => 'Report à nouveau',

                'mode' => 'Virement',        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'Test123');        $ex2026 = new Exercice();

                'personne' => null,

            ],        $adminUser->setPassword($hashedPassword);        $ex2026->setLibelle('Exercice Comptable 2026');

            [

                'date' => '2023-12-06',                $ex2026->setDateDebut(new \DateTime('2026-01-01'));

                'libelle' => 'Cotisation GAUTHEROT Didier',

                'montant' => '30.00',        // Ajouter tous les rôles à l'admin        $ex2026->setDateFin(new \DateTime('2026-12-31'));

                'numero_ordre' => 11,

                'type' => 'Cotisation',        foreach ($roles as $role) {        $ex2026->setNumeroOrdre(4);

                'mode' => 'Chèque',

                'personne' => 'gautherot_didier',            $adminUser->addRole($role);        $ex2026->setClos(false);

            ],

            [        }        $manager->persist($ex2026);

                'date' => '2023-12-20',

                'libelle' => 'Cotisation CLAY Jacques',                $exercices['2026'] = $ex2026;

                'montant' => '30.00',

                'numero_ordre' => 12,        // Associer l'admin à l'entreprise

                'type' => 'Cotisation',

                'mode' => 'Chèque',        $adminPersonne->addEntreprise($entreprise);        return $exercices;

                'personne' => 'clay_jacques',

            ],            }

            [

                'date' => '2023-12-28',        $manager->persist($adminUser);

                'libelle' => 'Cotisation HERBST David',

                'montant' => '100.00',    private function createModesPaiement(ObjectManager $manager): array

                'numero_ordre' => 24,

                'type' => 'Cotisation',        // Créer quelques autres utilisateurs avec le rôle USER    {

                'mode' => 'Chèque',

                'personne' => 'herbst_david',        $users = [$adminUser];        $modes = [];

            ],

            [        

                'date' => '2024-01-03',

                'libelle' => 'Cotisation BEGUET Pierre',        // Créer un utilisateur trésorier (David Herbst)        $modesPaiementData = [

                'montant' => '150.00',

                'numero_ordre' => 14,        $treasurerPersonne = $personnes[2]; // David HERBST            ['especes', 'Espèces', 'Paiement en liquide'],

                'type' => 'Cotisation',

                'mode' => 'Chèque',        $treasurerUser = new User();            ['cheque', 'Chèque', 'Paiement par chèque bancaire'],

                'personne' => 'beguet_pierre',

            ],        $treasurerUser->setUsername('david.herbst');            ['virement', 'Virement', 'Virement bancaire'],

        ];

        $treasurerUser->setPersonne($treasurerPersonne);            ['carte_bancaire', 'Carte bancaire', 'Paiement par carte bancaire'],

        // Exercice 2024-2025 (transactions principales basées sur le fichier)

        $exercice2024 = $exercices[2];        $hashedPassword = $this->passwordHasher->hashPassword($treasurerUser, 'Test123');            ['prelevement', 'Prélèvement', 'Prélèvement automatique'],

        $transactions2024 = [

            [        $treasurerUser->setPassword($hashedPassword);            ['livret', 'Livret', 'Opération sur livret d\'épargne'],

                'date' => '2024-09-01',

                'libelle' => 'Report à nouveau exercice 2024-2025',        $treasurerUser->addRole($roles[0]); // ROLE_USER            ['facture', 'Facture', 'Paiement sur facture (délai)'],

                'montant' => '4357.34',

                'numero_ordre' => 1,        $treasurerUser->addRole($roles[3]); // ROLE_TREASURER        ];

                'type' => 'Report à nouveau',

                'mode' => 'Virement',        $treasurerPersonne->addEntreprise($entreprise);

                'personne' => null,

            ],                foreach ($modesPaiementData as [$code, $libelle, $description]) {

            [

                'date' => '2024-09-18',        $manager->persist($treasurerUser);            $mode = new ModeDePaiement();

                'libelle' => 'Cotisation BOULINGUIEZ Jean-Paul',

                'montant' => '30.00',        $users[] = $treasurerUser;            $mode->setLibelle($libelle);

                'numero_ordre' => 4,

                'type' => 'Cotisation',            $manager->persist($mode);

                'mode' => 'Espèces',

                'personne' => 'boulinguiez_jean-paul',        return $users;            $modes[$code] = $mode;

            ],

            [    }        }

                'date' => '2024-11-14',

                'libelle' => 'Cotisation FASQUELLE Yves',

                'montant' => '35.00',

                'numero_ordre' => 7,    private function createModesPaiement(ObjectManager $manager): array        return $modes;

                'type' => 'Cotisation',

                'mode' => 'Virement',    {    }

                'personne' => 'fasquelle_yves',

            ],        $modesData = [

            [

                'date' => '2024-11-25',            ['libelle' => 'Espèces', 'description' => 'Paiement en espèces'],    private function createTypesTransaction(ObjectManager $manager): array

                'libelle' => 'Cotisation TOUTAIN Nicolas',

                'montant' => '100.00',            ['libelle' => 'Chèque', 'description' => 'Paiement par chèque'],    {

                'numero_ordre' => 19,

                'type' => 'Cotisation',            ['libelle' => 'Virement', 'description' => 'Paiement par virement bancaire'],        $types = [];

                'mode' => 'Virement',

                'personne' => 'toutain_nicolas',            ['libelle' => 'Carte bancaire', 'description' => 'Paiement par carte bancaire'],

            ],

            [            ['libelle' => 'Prélèvement', 'description' => 'Prélèvement automatique'],        $typesData = [

                'date' => '2024-11-25',

                'libelle' => 'Cotisation DEMARETZ Paul',        ];            ['cotisation', 'Cotisation', 'Cotisations des membres (ouvre droit aux attestations fiscales)', 'credit'],

                'montant' => '50.00',

                'numero_ordre' => 41,            ['subvention', 'Subvention', 'Subventions publiques et privées', 'credit'],

                'type' => 'Cotisation',

                'mode' => 'Chèque',        $modes = [];            ['sponsoring', 'Sponsoring', 'Partenariats et sponsoring', 'credit'],

                'personne' => 'desmaretz_paul',

            ],        foreach ($modesData as $data) {            ['vente_evenement', 'Vente événement', 'Ventes lors d\'événements (buvette, tombola, etc.)', 'credit'],

            [

                'date' => '2024-11-25',            $mode = new ModeDePaiement();            ['don', 'Don', 'Dons et mécénat', 'credit'],

                'libelle' => 'Cotisation BEGUET Pierre',

                'montant' => '150.00',            $mode->setLibelle($data['libelle']);            ['remboursement', 'Remboursement', 'Remboursements divers', 'both'],

                'numero_ordre' => 42,

                'type' => 'Cotisation',            $mode->setDescription($data['description']);            ['achat_equipement', 'Achat équipement', 'Achat matériel et équipements sportifs', 'debit'],

                'mode' => 'Chèque',

                'personne' => 'beguet_pierre',            $manager->persist($mode);            ['frais_deplacement', 'Frais déplacement', 'Frais de déplacement et hébergement', 'debit'],

            ],

            [            $modes[] = $mode;            ['assurance', 'Assurance', 'Assurances et licences fédérales', 'debit'],

                'date' => '2024-11-25',

                'libelle' => 'Cotisation HERBST David',        }            ['maintenance', 'Maintenance', 'Maintenance et réparations', 'debit'],

                'montant' => '100.00',

                'numero_ordre' => 45,            ['communication', 'Communication', 'Frais de communication et marketing', 'debit'],

                'type' => 'Cotisation',

                'mode' => 'Espèces',        return $modes;            ['administration', 'Administration', 'Frais administratifs et bancaires', 'debit'],

                'personne' => 'herbst_david',

            ],    }            ['formation', 'Formation', 'Formation des entraîneurs et dirigeants', 'debit'],

            [

                'date' => '2024-12-31',            ['arbitrage', 'Arbitrage', 'Frais d\'arbitrage et organisation', 'debit'],

                'libelle' => 'Cotisation STEENKESTE Cédric',

                'montant' => '300.00',    private function createTypesTransaction(ObjectManager $manager): array            ['transfert_livret', 'Transfert livret', 'Transferts vers/depuis le livret d\'épargne', 'both'],

                'numero_ordre' => 80,

                'type' => 'Cotisation',    {        ];

                'mode' => 'Virement',

                'personne' => 'steenkeste_cédric',        $typesData = [

            ],

            [            ['libelle' => 'Cotisation', 'description' => 'Cotisation annuelle des membres', 'nature' => 'recette'],        foreach ($typesData as [$code, $libelle, $description, $montantAutorise]) {

                'date' => '2025-01-03',

                'libelle' => 'Cotisation GUILBERT Kévin',            ['libelle' => 'Repas amicale', 'description' => 'Paiement repas amicale', 'nature' => 'recette'],            $type = new TypeTransaction();

                'montant' => '20.00',

                'numero_ordre' => 82,            ['libelle' => 'Achat repas', 'description' => 'Achat nourriture pour repas', 'nature' => 'depense'],            $type->setLibelle($libelle);

                'type' => 'Cotisation',

                'mode' => 'Virement',            ['libelle' => 'Achat vaisselle', 'description' => 'Achat de vaisselle', 'nature' => 'depense'],            $type->setDescription($description);

                'personne' => 'guilbert_kévin',

            ],            ['libelle' => 'Achat fleurs', 'description' => 'Achat de fleurs', 'nature' => 'depense'],            $type->setTypeMontantAutorise($montantAutorise);

            // Quelques dépenses

            [            ['libelle' => 'Tenue de compte', 'description' => 'Frais bancaires', 'nature' => 'depense'],            $manager->persist($type);

                'date' => '2024-09-12',

                'libelle' => 'Participation école de rugby - remboursement RCA',            ['libelle' => 'Ristourne', 'description' => 'Ristourne bancaire', 'nature' => 'recette'],            $types[$code] = $type;

                'montant' => '-607.60',

                'numero_ordre' => 3,            ['libelle' => 'Cadeaux', 'description' => 'Achat de cadeaux', 'nature' => 'depense'],        }

                'type' => 'Participation école de rugby',

                'mode' => 'Virement',            ['libelle' => 'Boisson', 'description' => 'Achat de boissons', 'nature' => 'depense'],

                'personne' => null,

            ],            ['libelle' => 'Participation école de rugby', 'description' => 'Participation aux activités du club', 'nature' => 'depense'],        return $types;

            [

                'date' => '2024-10-25',            ['libelle' => 'Report à nouveau', 'description' => 'Report de solde d\'exercice', 'nature' => 'recette'],    }

                'libelle' => 'Achat vaisselle arc outlet',

                'montant' => '-239.00',            ['libelle' => 'Virement livret', 'description' => 'Virement vers/depuis livret d\'épargne', 'nature' => 'neutre'],

                'numero_ordre' => 15,

                'type' => 'Achat vaisselle',        ];    private function createPersonnes(ObjectManager $manager): array

                'mode' => 'Carte bancaire',

                'personne' => null,    {

            ],

            [        $types = [];        $personnes = [];

                'date' => '2024-11-24',

                'libelle' => 'Pain tarte repas - la miche d\'or',        foreach ($typesData as $data) {

                'montant' => '-145.10',

                'numero_ordre' => 20,            $type = new TypeTransaction();        $personnesData = [

                'type' => 'Achat repas',

                'mode' => 'Chèque',            $type->setLibelle($data['libelle']);            ['M.', 'Dupont', 'Jean', '123 rue de la Paix', 'Paris', 75001, '0123456789', 'jean.dupont@email.fr'],

                'personne' => null,

            ],            $type->setDescription($data['description']);            ['Mme', 'Martin', 'Marie', '456 avenue des Champs', 'Lyon', 69000, '0234567890', 'marie.martin@email.fr'],

            [

                'date' => '2024-11-25',            $type->setNature($data['nature']);            ['M.', 'Bernard', 'Pierre', '789 boulevard Saint-Michel', 'Marseille', 13000, '0345678901', 'pierre.bernard@email.fr'],

                'libelle' => 'Repas amicale - Marco traiteur',

                'montant' => '-1233.69',            $manager->persist($type);            ['Mlle', 'Dubois', 'Sophie', '321 rue Victor Hugo', 'Toulouse', 31000, '0456789012', 'sophie.dubois@email.fr'],

                'numero_ordre' => 74,

                'type' => 'Achat repas',            $types[] = $type;            ['M.', 'Moreau', 'Antoine', '654 place de la République', 'Nice', 06000, '0567890123', 'antoine.moreau@email.fr'],

                'mode' => 'Chèque',

                'personne' => null,        }            ['Mme', 'Petit', 'Claire', '987 rue Nationale', 'Lille', 59000, '0678901234', 'claire.petit@email.fr'],

            ],

            [            ['M.', 'Durand', 'François', '147 avenue de la Liberté', 'Strasbourg', 67000, '0789012345', 'francois.durand@email.fr'],

                'date' => '2024-11-25',

                'libelle' => 'Boissons - Cave de St Arnoult',        return $types;            ['Mme', 'Leroy', 'Isabelle', '258 rue de Rivoli', 'Nantes', 44000, '0890123456', 'isabelle.leroy@email.fr'],

                'montant' => '-303.59',

                'numero_ordre' => 73,    }            ['M.', 'Roux', 'Thomas', '369 cours Lafayette', 'Bordeaux', 33000, '0901234567', 'thomas.roux@email.fr'],

                'type' => 'Boisson',

                'mode' => 'Chèque',            ['Mme', 'Vincent', 'Caroline', '741 rue de la Gare', 'Montpellier', 34000, '0912345678', 'caroline.vincent@email.fr'],

                'personne' => null,

            ],    private function createExercices(ObjectManager $manager, User $user): array        ];

        ];

    {

        // Créer les transactions pour l'exercice 2023-2024

        foreach ($transactions2023 as $data) {        $exercicesData = [        foreach ($personnesData as [$civilite, $nom, $prenom, $adresse, $ville, $codePostal, $telephone, $email]) {

            $this->createTransaction($manager, $data, $exercice2023, $typesByLibelle, $modesByLibelle, $personnesByName);

        }            [            $personne = new Personne();



        // Créer les transactions pour l'exercice 2024-2025                'libelle' => 'Exercice 2022-2023',            $personne->setCivilite($civilite);

        foreach ($transactions2024 as $data) {

            $this->createTransaction($manager, $data, $exercice2024, $typesByLibelle, $modesByLibelle, $personnesByName);                'date_debut' => new \DateTime('2022-09-01'),            $personne->setNom($nom);

        }

    }                'date_fin' => new \DateTime('2023-08-31'),            $personne->setPrenom($prenom);



    private function createTransaction(ObjectManager $manager, array $data, Exercice $exercice, array $typesByLibelle, array $modesByLibelle, array $personnesByName): void                'solde_initial' => '0.00',            $personne->setRue($adresse);

    {

        $transaction = new Transaction();                'est_cloture' => true,            $personne->setVille($ville);

        $transaction->setLibelle($data['libelle']);

        $transaction->setMontant($data['montant']);            ],            $personne->setCodePostal($codePostal);

        $transaction->setNumeroOrdre($data['numero_ordre']);

        $transaction->setDateTransaction(new \DateTime($data['date']));            [            $personne->setTelephone(intval($telephone));

        $transaction->setExercice($exercice);

        $transaction->setTypeCompte('compte_courant');                'libelle' => 'Exercice 2023-2024',            $personne->setEmail($email);

        

        // Associer le type de transaction                'date_debut' => new \DateTime('2023-10-01'),            

        if (isset($typesByLibelle[$data['type']])) {

            $transaction->setTypeTransaction($typesByLibelle[$data['type']]);                'date_fin' => new \DateTime('2024-08-31'),            $manager->persist($personne);

        }

                        'solde_initial' => '3225.89',            $personnes[strtolower($nom)] = $personne;

        // Associer le mode de paiement

        if (isset($modesByLibelle[$data['mode']])) {                'est_cloture' => true,        }

            $transaction->setModeDePaiement($modesByLibelle[$data['mode']]);

        }            ],

        

        // Associer la personne si elle existe            [        return $personnes;

        if ($data['personne'] && isset($personnesByName[$data['personne']])) {

            $transaction->setPersonne($personnesByName[$data['personne']]);                'libelle' => 'Exercice 2024-2025',    }

        }

                        'date_debut' => new \DateTime('2024-09-01'),

        $manager->persist($transaction);

    }                'date_fin' => new \DateTime('2025-08-31'),    private function createEntreprises(ObjectManager $manager): array

}
                'solde_initial' => '4357.34',    {

                'est_cloture' => false,        $entreprises = [];

            ],

        ];        $entreprisesData = [

            ['Mairie de Toulouse', '21310555600019', '213105556', '1 place du Capitole', 'Toulouse', 31000, '0561223344', 'mairie@toulouse.fr'],

        $exercices = [];            ['Région Occitanie', '23310000400019', '233100004', '22 boulevard du Maréchal Juin', 'Toulouse', 31406, '0567788990', 'contact@laregion.fr'],

        foreach ($exercicesData as $data) {            ['Crédit Agricole Toulouse', '78142420273', '781424202', '15 place Wilson', 'Toulouse', 31000, '0825005050', 'toulouse@ca-toulouse.fr'],

            $exercice = new Exercice();            ['Décathlon Toulouse', '30235032800156', '302350328', 'ZAC de Gramont', 'Toulouse', 31200, '0562200300', 'toulouse@decathlon.fr'],

            $exercice->setLibelle($data['libelle']);            ['Intersport Toulouse', '32584502400019', '325845024', '2 rue de Metz', 'Toulouse', 31000, '0561123456', 'contact@intersport-toulouse.fr'],

            $exercice->setDateDebut($data['date_debut']);            ['Fédération Française de Rugby', '77568151300042', '775681513', '9 rue de Liège', 'Paris', 75009, '0153218200', 'ffr@ffr.fr'],

            $exercice->setDateFin($data['date_fin']);            ['Ligue Régionale Occitanie', '35248759600028', '352487596', '123 avenue de Muret', 'Toulouse', 31300, '0561456789', 'ligue@occitanie-rugby.fr'],

            $exercice->setSoldeInitial($data['solde_initial']);            ['Gilbert Rugby', '31245896700015', '312458967', 'Zone Industrielle', 'Bourgoin-Jallieu', 38300, '0474282828', 'info@gilbert.fr'],

            $exercice->setEstCloture($data['est_cloture']);            ['Assurance MAIF', '77567227200394', '775672272', '200 avenue Salvador Allende', 'Niort', 79000, '0549733373', 'toulouse@maif.fr'],

            $exercice->setCreePar($user);            ['Livret Club RCA', '00000000000000', '000000000', 'Compte épargne club', 'Toulouse', 31000, '', 'livret@rca-toulouse.fr'],

            $exercice->setDateCreation(new \DateTime());        ];

            

            $manager->persist($exercice);        foreach ($entreprisesData as [$nom, $siret, $siren, $adresse, $ville, $codePostal, $telephone, $email]) {

            $exercices[] = $exercice;            $entreprise = new Entreprise();

        }            $entreprise->setNomEntreprise($nom);

            $entreprise->setSiret($siret);

        return $exercices;            $entreprise->setSiren($siren);

    }            $entreprise->setRue($adresse);

            $entreprise->setVille($ville);

    private function createTransactions(ObjectManager $manager, array $exercices, array $personnes, array $types, array $modes): void            $entreprise->setCodePostal($codePostal);

    {            if (!empty($telephone)) {

        // Index des types de transaction par libellé                $entreprise->setTelephone(intval(str_replace(' ', '', $telephone)));

        $typesByLibelle = [];            }

        foreach ($types as $type) {            $entreprise->setEmail($email);

            $typesByLibelle[$type->getLibelle()] = $type;            $manager->persist($entreprise);

        }            

            $key = strtolower(str_replace(['é', 'è', ' ', '-'], ['e', 'e', '_', '_'], explode(' ', $nom)[0]));

        // Index des modes de paiement par libellé            $entreprises[$key] = $entreprise;

        $modesByLibelle = [];        }

        foreach ($modes as $mode) {

            $modesByLibelle[$mode->getLibelle()] = $mode;        return $entreprises;

        }    }



        // Index des personnes par nom/prénom    private function createTransactions(ObjectManager $manager, array $exercices, array $types, array $modes, array $personnes, array $entreprises): void

        $personnesByName = [];    {

        foreach ($personnes as $personne) {        // ========== TRANSACTIONS 2023 (CLÔTURÉ) ==========

            $key = strtolower($personne->getNom() . '_' . $personne->getPrenom());        $this->createTransactions2023($manager, $exercices['2023'], $types, $modes, $personnes, $entreprises);

            $personnesByName[$key] = $personne;        

        }        // ========== TRANSACTIONS 2024 (CLÔTURÉ) ==========

        $this->createTransactions2024($manager, $exercices['2024'], $types, $modes, $personnes, $entreprises);

        // Exercice 2023-2024 (quelques transactions principales)        

        $exercice2023 = $exercices[1];        // ========== TRANSACTIONS 2025 (EN COURS) ==========

        $transactions2023 = [        $this->createTransactions2025($manager, $exercices['2025'], $types, $modes, $personnes, $entreprises);

            [    }

                'date' => '2024-01-10',

                'libelle' => 'Report à nouveau exercice 2023-2024',    private function createTransactions2023(ObjectManager $manager, Exercice $exercice, array $types, array $modes, array $personnes, array $entreprises): void

                'montant' => '3225.89',    {

                'numero_ordre' => 1,        $numeroOrdre = 1;

                'type' => 'Report à nouveau',

                'mode' => 'Virement',        // Janvier 2023 - Début d'exercice

                'personne' => null,        $transactions = [

            ],            ['Cotisation annuelle Jean Dupont', new \DateTime('2023-01-15'), '180.00', $types['cotisation'], $modes['virement'], $personnes['dupont'], null],

            [            ['Cotisation annuelle Marie Martin', new \DateTime('2023-01-20'), '180.00', $types['cotisation'], $modes['cheque'], $personnes['martin'], null],

                'date' => '2023-12-06',            ['Subvention municipale 2023', new \DateTime('2023-01-25'), '5000.00', $types['subvention'], $modes['virement'], null, $entreprises['mairie']],

                'libelle' => 'Cotisation GAUTHEROT Didier',            

                'montant' => '30.00',            // Février 2023

                'numero_ordre' => 11,            ['Achat maillots équipe première', new \DateTime('2023-02-10'), '-1250.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['decathlon']],

                'type' => 'Cotisation',            ['Cotisation Pierre Bernard', new \DateTime('2023-02-14'), '180.00', $types['cotisation'], $modes['especes'], $personnes['bernard'], null],

                'mode' => 'Chèque',            ['Frais bancaires janvier', new \DateTime('2023-02-28'), '-15.50', $types['administration'], $modes['prelevement'], null, $entreprises['credit']],

                'personne' => 'gautherot_didier',            

            ],            // Mars 2023

            [            ['Sponsoring local Intersport', new \DateTime('2023-03-05'), '800.00', $types['sponsoring'], $modes['virement'], null, $entreprises['intersport']],

                'date' => '2023-12-20',            ['Assurance responsabilité civile', new \DateTime('2023-03-12'), '-450.00', $types['assurance'], $modes['prelevement'], null, $entreprises['assurance']],

                'libelle' => 'Cotisation CLAY Jacques',            ['Cotisation Sophie Dubois', new \DateTime('2023-03-18'), '180.00', $types['cotisation'], $modes['cheque'], $personnes['dubois'], null],

                'montant' => '30.00',            

                'numero_ordre' => 12,            // Transfert vers livret en mars

                'type' => 'Cotisation',            ['Transfert vers livret épargne', new \DateTime('2023-03-25'), '-3000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

                'mode' => 'Chèque',            ['Dépôt livret épargne', new \DateTime('2023-03-25'), '3000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

                'personne' => 'clay_jacques',            

            ],            // Reste de l'année 2023 (résumé)

            [            ['Tournoi été - Recettes buvette', new \DateTime('2023-06-15'), '1200.00', $types['vente_evenement'], $modes['especes'], null, null],

                'date' => '2023-12-28',            ['Déplacement championnat région', new \DateTime('2023-06-20'), '-800.00', $types['frais_deplacement'], $modes['carte_bancaire'], null, null],

                'libelle' => 'Cotisation HERBST David',            ['Subvention régionale', new \DateTime('2023-09-10'), '2500.00', $types['subvention'], $modes['virement'], null, $entreprises['region']],

                'montant' => '100.00',            ['Maintenance terrain automne', new \DateTime('2023-10-15'), '-950.00', $types['maintenance'], $modes['facture'], null, null],

                'numero_ordre' => 24,            ['Formation arbitres', new \DateTime('2023-11-20'), '-300.00', $types['formation'], $modes['virement'], null, $entreprises['federation']],

                'type' => 'Cotisation',            ['Cotisations tardives décembre', new \DateTime('2023-12-15'), '540.00', $types['cotisation'], $modes['cheque'], $personnes['moreau'], null],

                'mode' => 'Chèque',        ];

                'personne' => 'herbst_david',

            ],        foreach ($transactions as [$libelle, $date, $montant, $type, $mode, $personne, $entreprise]) {

            [            $transaction = new Transaction();

                'date' => '2024-01-03',            $transaction->setLibelle($libelle);

                'libelle' => 'Cotisation BEGUET Pierre',            $transaction->setNumeroOrdre($numeroOrdre++);

                'montant' => '150.00',            $transaction->setDateTransaction($date);

                'numero_ordre' => 14,            $transaction->setMontant($montant);

                'type' => 'Cotisation',            $transaction->setExercice($exercice);

                'mode' => 'Chèque',            $transaction->setTypeTransaction($type);

                'personne' => 'beguet_pierre',            $transaction->setModeDePaiement($mode);

            ],            

        ];            if ($personne) {

                $transaction->setPersonne($personne);

        // Exercice 2024-2025 (transactions principales basées sur le fichier)            }

        $exercice2024 = $exercices[2];            if ($entreprise) {

        $transactions2024 = [                $transaction->setEntreprise($entreprise);

            [            }

                'date' => '2024-09-01',            

                'libelle' => 'Report à nouveau exercice 2024-2025',            // Gérer les transactions livret

                'montant' => '4357.34',            if ($type === $types['transfert_livret'] && $entreprise === $entreprises['livret']) {

                'numero_ordre' => 1,                $transaction->setTypeCompte('livret');

                'type' => 'Report à nouveau',            } else {

                'mode' => 'Virement',                $transaction->setTypeCompte('compte_courant');

                'personne' => null,            }

            ],            

            [            $manager->persist($transaction);

                'date' => '2024-09-18',        }

                'libelle' => 'Cotisation BOULINGUIEZ Jean-Paul',    }

                'montant' => '30.00',

                'numero_ordre' => 4,    private function createTransactions2024(ObjectManager $manager, Exercice $exercice, array $types, array $modes, array $personnes, array $entreprises): void

                'type' => 'Cotisation',    {

                'mode' => 'Espèces',        $numeroOrdre = 1;

                'personne' => 'boulinguiez_jean-paul',

            ],        $transactions = [

            [            // Janvier 2024

                'date' => '2024-11-14',            ['Cotisations janvier 2024 - Lot 1', new \DateTime('2024-01-10'), '720.00', $types['cotisation'], $modes['virement'], $personnes['dupont'], null],

                'libelle' => 'Cotisation FASQUELLE Yves',            ['Subvention municipale 2024', new \DateTime('2024-01-15'), '5500.00', $types['subvention'], $modes['virement'], null, $entreprises['mairie']],

                'montant' => '35.00',            ['Licence fédérale équipe', new \DateTime('2024-01-20'), '-890.00', $types['assurance'], $modes['prelevement'], null, $entreprises['federation']],

                'numero_ordre' => 7,            

                'type' => 'Cotisation',            // Février 2024

                'mode' => 'Virement',            ['Remboursement déplacement entraîneur', new \DateTime('2024-02-05'), '-125.50', $types['frais_deplacement'], $modes['especes'], $personnes['bernard'], null],

                'personne' => 'fasquelle_yves',            ['Sponsoring Décathlon 2024', new \DateTime('2024-02-12'), '1200.00', $types['sponsoring'], $modes['virement'], null, $entreprises['decathlon']],

            ],            ['Achat ballons entraînement', new \DateTime('2024-02-18'), '-285.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['gilbert']],

            [            

                'date' => '2024-11-25',            // Mars 2024 - Gros transfert vers livret

                'libelle' => 'Cotisation TOUTAIN Nicolas',            ['Transfert exceptionnel livret', new \DateTime('2024-03-15'), '-8000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

                'montant' => '100.00',            ['Placement livret exceptionnel', new \DateTime('2024-03-15'), '8000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

                'numero_ordre' => 19,            

                'type' => 'Cotisation',            // Avril à décembre 2024 (transactions variées)

                'mode' => 'Virement',            ['Don mécène local', new \DateTime('2024-04-20'), '1500.00', $types['don'], $modes['virement'], null, null],

                'personne' => 'toutain_nicolas',            ['Maintenance équipements', new \DateTime('2024-05-10'), '-750.00', $types['maintenance'], $modes['facture'], null, null],

            ],            ['Tournoi jeunes - Recettes', new \DateTime('2024-06-15'), '2100.00', $types['vente_evenement'], $modes['especes'], null, null],

            [            ['Frais arbitrage championnat', new \DateTime('2024-06-20'), '-450.00', $types['arbitrage'], $modes['carte_bancaire'], null, $entreprises['ligue']],

                'date' => '2024-11-25',            ['Cotisations été 2024', new \DateTime('2024-07-01'), '1080.00', $types['cotisation'], $modes['cheque'], $personnes['petit'], null],

                'libelle' => 'Cotisation DEMARETZ Paul',            ['Communication site web', new \DateTime('2024-08-15'), '-380.00', $types['communication'], $modes['prelevement'], null, null],

                'montant' => '50.00',            ['Subvention région automne', new \DateTime('2024-09-25'), '3200.00', $types['subvention'], $modes['virement'], null, $entreprises['region']],

                'numero_ordre' => 41,            ['Formation dirigeants', new \DateTime('2024-10-12'), '-580.00', $types['formation'], $modes['virement'], null, $entreprises['federation']],

                'type' => 'Cotisation',            ['Déplacement finale régionale', new \DateTime('2024-11-08'), '-1150.00', $types['frais_deplacement'], $modes['carte_bancaire'], null, null],

                'mode' => 'Chèque',            ['Cotisations fin d\'année', new \DateTime('2024-12-20'), '900.00', $types['cotisation'], $modes['virement'], $personnes['vincent'], null],

                'personne' => 'desmaretz_paul',            

            ],            // Transaction négative inhabituelle

            [            ['Remboursement cotisation - erreur', new \DateTime('2024-12-28'), '-180.00', $types['remboursement'], $modes['virement'], $personnes['durand'], null],

                'date' => '2024-11-25',        ];

                'libelle' => 'Cotisation BEGUET Pierre',

                'montant' => '150.00',        foreach ($transactions as [$libelle, $date, $montant, $type, $mode, $personne, $entreprise]) {

                'numero_ordre' => 42,            $transaction = new Transaction();

                'type' => 'Cotisation',            $transaction->setLibelle($libelle);

                'mode' => 'Chèque',            $transaction->setNumeroOrdre($numeroOrdre++);

                'personne' => 'beguet_pierre',            $transaction->setDateTransaction($date);

            ],            $transaction->setMontant($montant);

            [            $transaction->setExercice($exercice);

                'date' => '2024-11-25',            $transaction->setTypeTransaction($type);

                'libelle' => 'Cotisation HERBST David',            $transaction->setModeDePaiement($mode);

                'montant' => '100.00',            

                'numero_ordre' => 45,            if ($personne) {

                'type' => 'Cotisation',                $transaction->setPersonne($personne);

                'mode' => 'Espèces',            }

                'personne' => 'herbst_david',            if ($entreprise) {

            ],                $transaction->setEntreprise($entreprise);

            [            }

                'date' => '2024-12-31',            

                'libelle' => 'Cotisation STEENKESTE Cédric',            if ($type === $types['transfert_livret'] && $entreprise === $entreprises['livret']) {

                'montant' => '300.00',                $transaction->setTypeCompte('livret');

                'numero_ordre' => 80,            } else {

                'type' => 'Cotisation',                $transaction->setTypeCompte('compte_courant');

                'mode' => 'Virement',            }

                'personne' => 'steenkeste_cédric',            

            ],            $manager->persist($transaction);

            [        }

                'date' => '2025-01-03',    }

                'libelle' => 'Cotisation GUILBERT Kévin',

                'montant' => '20.00',    private function createTransactions2025(ObjectManager $manager, Exercice $exercice, array $types, array $modes, array $personnes, array $entreprises): void

                'numero_ordre' => 82,    {

                'type' => 'Cotisation',        $numeroOrdre = 1;

                'mode' => 'Virement',

                'personne' => 'guilbert_kévin',        $transactions = [

            ],            // Janvier 2025 - Début d'année

            // Quelques dépenses            ['Cotisations nouvelle saison - Dupont', new \DateTime('2025-01-08'), '200.00', $types['cotisation'], $modes['virement'], $personnes['dupont'], null],

            [            ['Cotisations nouvelle saison - Martin', new \DateTime('2025-01-08'), '200.00', $types['cotisation'], $modes['prelevement'], $personnes['martin'], null],

                'date' => '2024-09-12',            ['Subvention municipale 2025', new \DateTime('2025-01-12'), '6000.00', $types['subvention'], $modes['virement'], null, $entreprises['mairie']],

                'libelle' => 'Participation école de rugby - remboursement RCA',            ['Frais bancaires décembre', new \DateTime('2025-01-15'), '-18.50', $types['administration'], $modes['prelevement'], null, $entreprises['credit']],

                'montant' => '-607.60',            ['Licence FFR saison 2025', new \DateTime('2025-01-20'), '-1200.00', $types['assurance'], $modes['prelevement'], null, $entreprises['federation']],

                'numero_ordre' => 3,            

                'type' => 'Participation école de rugby',            // Février 2025

                'mode' => 'Virement',            ['Cotisations février - Bernard', new \DateTime('2025-02-03'), '200.00', $types['cotisation'], $modes['cheque'], $personnes['bernard'], null],

                'personne' => null,            ['Cotisations février - Dubois', new \DateTime('2025-02-03'), '200.00', $types['cotisation'], $modes['carte_bancaire'], $personnes['dubois'], null],

            ],            ['Achat équipement hiver', new \DateTime('2025-02-10'), '-850.00', $types['achat_equipement'], $modes['facture'], null, $entreprises['gilbert']],

            [            ['Remboursement essence déplacement', new \DateTime('2025-02-14'), '-95.00', $types['frais_deplacement'], $modes['especes'], $personnes['moreau'], null],

                'date' => '2024-10-25',            ['Sponsoring annuel Intersport', new \DateTime('2025-02-20'), '1500.00', $types['sponsoring'], $modes['virement'], null, $entreprises['intersport']],

                'libelle' => 'Achat vaisselle arc outlet',            

                'montant' => '-239.00',            // Mars 2025

                'numero_ordre' => 15,            ['Cotisations mars - Petit', new \DateTime('2025-03-02'), '200.00', $types['cotisation'], $modes['virement'], $personnes['petit'], null],

                'type' => 'Achat vaisselle',            ['Cotisations mars - Durand', new \DateTime('2025-03-05'), '200.00', $types['cotisation'], $modes['cheque'], $personnes['durand'], null],

                'mode' => 'Carte bancaire',            ['Maintenance terrain printemps', new \DateTime('2025-03-12'), '-680.00', $types['maintenance'], $modes['carte_bancaire'], null, null],

                'personne' => null,            ['Assurance matériel club', new \DateTime('2025-03-18'), '-520.00', $types['assurance'], $modes['prelevement'], null, $entreprises['assurance']],

            ],            ['Formation secourisme', new \DateTime('2025-03-25'), '-240.00', $types['formation'], $modes['virement'], null, null],

            [            

                'date' => '2024-11-24',            // Avril 2025

                'libelle' => 'Pain tarte repas - la miche d\'or',            ['Cotisations avril - Leroy', new \DateTime('2025-04-07'), '200.00', $types['cotisation'], $modes['prelevement'], $personnes['leroy'], null],

                'montant' => '-145.10',            ['Cotisations avril - Roux', new \DateTime('2025-04-10'), '200.00', $types['cotisation'], $modes['virement'], $personnes['roux'], null],

                'numero_ordre' => 20,            ['Don généreux supporter', new \DateTime('2025-04-15'), '500.00', $types['don'], $modes['cheque'], null, null],

                'type' => 'Achat repas',            ['Communication flyers tournoi', new \DateTime('2025-04-20'), '-180.00', $types['communication'], $modes['carte_bancaire'], null, null],

                'mode' => 'Chèque',            

                'personne' => null,            // Mai 2025

            ],            ['Cotisations mai - Vincent', new \DateTime('2025-05-05'), '200.00', $types['cotisation'], $modes['carte_bancaire'], $personnes['vincent'], null],

            [            ['Subvention région Occitanie', new \DateTime('2025-05-10'), '4200.00', $types['subvention'], $modes['virement'], null, $entreprises['region']],

                'date' => '2024-11-25',            ['Déplacement équipe jeunes', new \DateTime('2025-05-15'), '-320.00', $types['frais_deplacement'], $modes['especes'], null, null],

                'libelle' => 'Repas amicale - Marco traiteur',            ['Achat matériel médical', new \DateTime('2025-05-22'), '-165.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['decathlon']],

                'montant' => '-1233.69',            

                'numero_ordre' => 74,            // Juin 2025 - Tournoi d'été

                'type' => 'Achat repas',            ['Tournoi été - Inscriptions', new \DateTime('2025-06-01'), '1800.00', $types['vente_evenement'], $modes['especes'], null, null],

                'mode' => 'Chèque',            ['Tournoi été - Buvette samedi', new \DateTime('2025-06-07'), '450.00', $types['vente_evenement'], $modes['especes'], null, null],

                'personne' => null,            ['Tournoi été - Buvette dimanche', new \DateTime('2025-06-08'), '380.00', $types['vente_evenement'], $modes['especes'], null, null],

            ],            ['Frais organisation tournoi', new \DateTime('2025-06-10'), '-420.00', $types['arbitrage'], $modes['carte_bancaire'], null, $entreprises['ligue']],

            [            

                'date' => '2024-11-25',            // Juillet 2025

                'libelle' => 'Boissons - Cave de St Arnoult',            ['Transfert vers livret été', new \DateTime('2025-07-05'), '-5000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

                'montant' => '-303.59',            ['Dépôt livret été 2025', new \DateTime('2025-07-05'), '5000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

                'numero_ordre' => 73,            ['Maintenance équipement été', new \DateTime('2025-07-12'), '-280.00', $types['maintenance'], $modes['facture'], null, null],

                'type' => 'Boisson',            

                'mode' => 'Chèque',            // Août - Septembre 2025

                'personne' => null,            ['Préparation nouvelle saison', new \DateTime('2025-08-20'), '-750.00', $types['achat_equipement'], $modes['carte_bancaire'], null, $entreprises['gilbert']],

            ],            ['Stage été entraîneurs', new \DateTime('2025-08-25'), '-490.00', $types['formation'], $modes['virement'], null, $entreprises['federation']],

        ];            ['Retrait partiel livret', new \DateTime('2025-09-10'), '2000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

            ['Prélèvement partiel livret', new \DateTime('2025-09-10'), '-2000.00', $types['transfert_livret'], $modes['livret'], null, $entreprises['livret']],

        // Créer les transactions pour l'exercice 2023-2024            

        foreach ($transactions2023 as $data) {            // Octobre 2025 - Transactions récentes

            $this->createTransaction($manager, $data, $exercice2023, $typesByLibelle, $modesByLibelle, $personnesByName);            ['Sponsoring nouveau partenaire', new \DateTime('2025-10-05'), '2500.00', $types['sponsoring'], $modes['virement'], null, null],

        }            ['Réparation vestiaires', new \DateTime('2025-10-12'), '-1200.00', $types['maintenance'], $modes['facture'], null, null],

            ['Cotisations rattrapage octobre', new \DateTime('2025-10-20'), '600.00', $types['cotisation'], $modes['cheque'], null, null],

        // Créer les transactions pour l'exercice 2024-2025            ['Frais déplacement octobre', new \DateTime('2025-10-25'), '-180.00', $types['frais_deplacement'], $modes['carte_bancaire'], null, null],

        foreach ($transactions2024 as $data) {            

            $this->createTransaction($manager, $data, $exercice2024, $typesByLibelle, $modesByLibelle, $personnesByName);            // Novembre 2025 - Plus récent

        }            ['Don club partenaire', new \DateTime('2025-11-02'), '800.00', $types['don'], $modes['virement'], null, null],

    }            ['Achat maillots nouveaux', new \DateTime('2025-11-08'), '-950.00', $types['achat_equipement'], $modes['facture'], null, $entreprises['decathlon']],

            ['Formation arbitres novembre', new \DateTime('2025-11-15'), '-320.00', $types['formation'], $modes['carte_bancaire'], null, $entreprises['ligue']],

    private function createTransaction(ObjectManager $manager, array $data, Exercice $exercice, array $typesByLibelle, array $modesByLibelle, array $personnesByName): void            ['Remboursement frais médical', new \DateTime('2025-11-20'), '-85.00', $types['remboursement'], $modes['especes'], $personnes['bernard'], null],

    {        ];

        $transaction = new Transaction();

        $transaction->setLibelle($data['libelle']);        foreach ($transactions as [$libelle, $date, $montant, $type, $mode, $personne, $entreprise]) {

        $transaction->setMontant($data['montant']);            $transaction = new Transaction();

        $transaction->setNumeroOrdre($data['numero_ordre']);            $transaction->setLibelle($libelle);

        $transaction->setDateTransaction(new \DateTime($data['date']));            $transaction->setNumeroOrdre($numeroOrdre++);

        $transaction->setExercice($exercice);            $transaction->setDateTransaction($date);

        $transaction->setTypeCompte('compte_courant');            $transaction->setMontant($montant);

                    $transaction->setExercice($exercice);

        // Associer le type de transaction            $transaction->setTypeTransaction($type);

        if (isset($typesByLibelle[$data['type']])) {            $transaction->setModeDePaiement($mode);

            $transaction->setTypeTransaction($typesByLibelle[$data['type']]);            

        }            if ($personne) {

                        $transaction->setPersonne($personne);

        // Associer le mode de paiement            }

        if (isset($modesByLibelle[$data['mode']])) {            if ($entreprise) {

            $transaction->setModeDePaiement($modesByLibelle[$data['mode']]);                $transaction->setEntreprise($entreprise);

        }            }

                    

        // Associer la personne si elle existe            if ($type === $types['transfert_livret'] && $entreprise === $entreprises['livret']) {

        if ($data['personne'] && isset($personnesByName[$data['personne']])) {                $transaction->setTypeCompte('livret');

            $transaction->setPersonne($personnesByName[$data['personne']]);            } else {

        }                $transaction->setTypeCompte('compte_courant');

                    }

        $manager->persist($transaction);            

    }            $manager->persist($transaction);

}        }
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