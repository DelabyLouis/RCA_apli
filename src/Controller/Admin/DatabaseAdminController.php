<?php

namespace App\Controller\Admin;

use App\Repository\ExerciceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Process;

#[Route('/maintenance')]
class DatabaseAdminController extends AbstractController
{
    public function __construct(
        private ExerciceRepository $exerciceRepository,
        private EntityManagerInterface $entityManager,
        private Connection $connection
    ) {}

    #[Route('', name: 'maintenance_database_admin')]
    public function index(Request $request): Response
    {
        // Token de sécurité simple
        $expectedToken = 'rca2024admin';
        $token = $request->query->get('token');
        
        if ($token !== $expectedToken) {
            return new Response('Accès non autorisé. URL: /maintenance?token=rca2024admin', 403);
        }

        $action = $request->query->get('action', 'menu');
        
        switch ($action) {
            case 'menu':
                return $this->renderMainMenu($expectedToken);
            case 'clean-duplicates-preview':
                return $this->previewCleanDuplicates($expectedToken);
            case 'clean-duplicates-execute':
                return $this->executeCleanDuplicates($expectedToken);
            case 'reset-database-preview':
                return $this->previewResetDatabase($expectedToken);
            case 'reset-database-execute':
                return $this->executeResetDatabase($expectedToken);
            default:
                return new Response('Action inconnue', 400);
        }
    }

    private function renderMainMenu(string $token): Response
    {
        $stats = $this->getDatabaseStats();
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Administration Base de Données - RCA Amicale</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
                .header { background: #007bff; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                .card { background: white; border: 1px solid #ddd; border-radius: 8px; margin: 15px 0; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
                .stat { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
                .btn { display: inline-block; padding: 12px 24px; margin: 8px 4px; text-decoration: none; border-radius: 5px; font-weight: bold; text-align: center; }
                .btn-info { background: #17a2b8; color: white; }
                .btn-warning { background: #ffc107; color: black; }
                .btn-danger { background: #dc3545; color: white; }
                .btn-secondary { background: #6c757d; color: white; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🔧 Administration Base de Données</h1>
                <p>Rugby Club Audomarois - Amicale</p>
            </div>

            <div class="card">
                <h2>📊 État actuel de la base</h2>
                <div class="stats">
                    <div class="stat">
                        <div class="stat-number">' . $stats['exercices'] . '</div>
                        <div>Exercices</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">' . $stats['transactions'] . '</div>
                        <div>Transactions</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">' . $stats['users'] . '</div>
                        <div>Utilisateurs</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">' . $stats['duplicates'] . '</div>
                        <div>Doublons détectés</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>🧹 Actions de nettoyage</h2>
                <p>Nettoyer les doublons sans perdre de données importantes</p>
                <a href="?token=' . $token . '&action=clean-duplicates-preview" class="btn btn-warning">🔍 Prévisualiser nettoyage des doublons</a>
            </div>

            <div class="card">
                <h2>⚠️ Actions de réinitialisation</h2>
                <div class="warning">
                    <strong>DANGER:</strong> Ces actions suppriment TOUTES les données et recréent la base à zéro avec les fixtures.
                </div>
                <p>Remise à zéro complète de la base de données</p>
                <a href="?token=' . $token . '&action=reset-database-preview" class="btn btn-danger">💣 Prévisualiser reset complet</a>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="/exercice" class="btn btn-secondary">← Retour à l\'application</a>
            </div>
        </body>
        </html>';

        return new Response($html);
    }

    private function getDatabaseStats(): array
    {
        try {
            $exercices = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];
            $transactions = $this->connection->executeQuery('SELECT COUNT(*) as count FROM transaction')->fetchAssociative()['count'];
            $users = $this->connection->executeQuery('SELECT COUNT(*) as count FROM "user"')->fetchAssociative()['count'];
            
            // Compter les doublons d'exercices
            $duplicatesQuery = "
                SELECT COUNT(*) as count FROM (
                    SELECT numero_ordre, libelle, COUNT(*) as dup_count
                    FROM exercice 
                    GROUP BY numero_ordre, libelle 
                    HAVING COUNT(*) > 1
                ) duplicates
            ";
            $duplicates = $this->connection->executeQuery($duplicatesQuery)->fetchAssociative()['count'];
            
            return [
                'exercices' => $exercices,
                'transactions' => $transactions,
                'users' => $users,
                'duplicates' => $duplicates
            ];
        } catch (\Exception $e) {
            return ['exercices' => 'N/A', 'transactions' => 'N/A', 'users' => 'N/A', 'duplicates' => 'N/A'];
        }
    }

    private function previewCleanDuplicates(string $token): Response
    {
        $duplicates = $this->getDuplicates();
        $totalBefore = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];
        
        if (empty($duplicates)) {
            return new Response('
                <h1>🎉 Aucun doublon trouvé !</h1>
                <p>Total exercices: ' . $totalBefore . '</p>
                <a href="?token=' . $token . '">← Retour menu</a>
            ');
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Nettoyage des doublons - Preview</title></head>
        <body style="font-family: Arial; max-width: 800px; margin: 0 auto; padding: 20px;">
            <h1>🧹 Nettoyage des exercices dupliqués</h1>
            
            <div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;">
                <strong>⚠️ ATTENTION:</strong> Cette opération va supprimer définitivement les doublons de la base de données.
            </div>

            <h2>État actuel:</h2>
            <p><strong>Total exercices:</strong> ' . $totalBefore . '</p>
            
            <h2>Doublons détectés:</h2>';

        $totalToDelete = 0;
        foreach ($duplicates as $duplicate) {
            $keepId = $duplicate['keep_id'];
            $numeroOrdre = $duplicate['numero_ordre'];
            $libelle = $duplicate['libelle'];
            $duplicateCount = $duplicate['duplicate_count'];
            $toDeleteCount = $duplicateCount - 1;
            $totalToDelete += $toDeleteCount;

            $html .= '<div style="background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 3px;">
                <strong>' . htmlspecialchars($libelle) . '</strong> (N°' . $numeroOrdre . ')<br>
                Copies: ' . $duplicateCount . ' | Garder ID: ' . $keepId . ' | Supprimer: ' . $toDeleteCount . '
            </div>';
        }

        $html .= '
            <h2>Résumé:</h2>
            <p>Exercices à supprimer: <strong>' . $totalToDelete . '</strong></p>
            <p>Exercices après nettoyage: <strong>' . ($totalBefore - $totalToDelete) . '</strong></p>

            <div style="margin-top: 30px;">
                <a href="?token=' . $token . '&action=clean-duplicates-execute" 
                   style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;"
                   onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ' . $totalToDelete . ' exercices ?\')">
                   🗑️ Supprimer les doublons
                </a>
                <a href="?token=' . $token . '" 
                   style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                   ← Annuler
                </a>
            </div>
        </body>
        </html>';

        return new Response($html);
    }

    private function executeCleanDuplicates(string $token): Response
    {
        $duplicates = $this->getDuplicates();
        $totalBefore = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];
        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            $keepId = $duplicate['keep_id'];
            $numeroOrdre = $duplicate['numero_ordre'];
            $libelle = $duplicate['libelle'];

            $deleteSql = "
                DELETE FROM exercice 
                WHERE numero_ordre = :numero_ordre 
                AND libelle = :libelle 
                AND id_exercice != :keep_id
            ";
            
            $deletedCount = $this->connection->executeStatement($deleteSql, [
                'numero_ordre' => $numeroOrdre,
                'libelle' => $libelle,
                'keep_id' => $keepId
            ]);

            $totalDeleted += $deletedCount;
        }

        $totalAfter = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];
        $this->entityManager->clear();

        return new Response('
            <h1>✅ Nettoyage terminé !</h1>
            <p>Exercices avant: ' . $totalBefore . '</p>
            <p>Exercices après: ' . $totalAfter . '</p>
            <p>Exercices supprimés: ' . $totalDeleted . '</p>
            <a href="/exercice" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🎉 Voir les exercices nettoyés</a>
        ');
    }

    private function previewResetDatabase(string $token): Response
    {
        $stats = $this->getDatabaseStats();

        return new Response('
        <!DOCTYPE html>
        <html>
        <head><title>Reset complet BDD - Preview</title></head>
        <body style="font-family: Arial; max-width: 800px; margin: 0 auto; padding: 20px;">
            <h1>💣 Reset complet de la base de données</h1>
            
            <div style="background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb;">
                <strong>🚨 DANGER EXTRÊME:</strong> Cette action va SUPPRIMER DÉFINITIVEMENT toutes les données actuelles et recréer une base propre avec les fixtures par défaut.
            </div>

            <h2>Données actuelles (qui seront perdues):</h2>
            <ul>
                <li><strong>' . $stats['exercices'] . '</strong> exercices</li>
                <li><strong>' . $stats['transactions'] . '</strong> transactions</li>
                <li><strong>' . $stats['users'] . '</strong> utilisateurs</li>
            </ul>

            <h2>Après le reset:</h2>
            <ul>
                <li><strong>3</strong> exercices (2022-2023, 2023-2024, 2024-2025)</li>
                <li><strong>~20</strong> transactions de test</li>
                <li><strong>1</strong> utilisateur admin</li>
            </ul>

            <div style="margin-top: 30px;">
                <a href="?token=' . $token . '&action=reset-database-execute" 
                   style="background: #dc3545; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;"
                   onclick="return confirm(\'ÊTES-VOUS ABSOLUMENT SÛR ? Cette action est IRRÉVERSIBLE et supprimera TOUTES les données actuelles !\')">
                   💀 CONFIRMER LE RESET COMPLET
                </a>
                <a href="?token=' . $token . '" 
                   style="background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; margin-left: 15px;">
                   🛡️ Annuler (recommandé)
                </a>
            </div>
        </body>
        </html>');
    }

    private function executeResetDatabase(string $token): Response
    {
        try {
            // 1. D'abord, récréer le schéma complet
            $projectRoot = dirname(__DIR__, 2);
            $schemaProcess = Process::fromShellCommandline('php bin/console doctrine:schema:drop --force --full-database', $projectRoot);
            $schemaProcess->run();
            
            $recreateProcess = Process::fromShellCommandline('php bin/console doctrine:schema:create', $projectRoot);
            $recreateProcess->run();
            
            // 2. Exécuter les migrations pour s'assurer que tout est à jour
            $migrateProcess = Process::fromShellCommandline('php bin/console doctrine:migrations:migrate --no-interaction', $projectRoot);
            $migrateProcess->run();
            
            // 3. Créer les données de base manuellement (plus fiable qu'un Process)
            $dataResult = $this->createBasicData();
            $dataStatus = $dataResult['success'] ? '✅ Données créées' : '❌ Erreur: ' . $dataResult['error'];
            
            // 4. Essayer les fixtures en plus si possible
            $process = Process::fromShellCommandline('php bin/console doctrine:fixtures:load --no-interaction', $projectRoot);
            $process->setTimeout(60);
            $process->run();
            
            $fixturesResult = $process->isSuccessful() ? 'avec fixtures en plus' : 'données de base uniquement';
            $errorInfo = $process->isSuccessful() ? '' : '<br><small>Détail fixtures: ' . $process->getErrorOutput() . '</small>';
            $errorInfo .= '<br><small>Données: ' . $dataStatus . '</small>';
            
            // 4. Vérifier si l'admin existe réellement (peu importe qui l'a créé)
            $adminExists = false;
            try {
                $adminCount = $this->connection->executeQuery('SELECT COUNT(*) FROM `user` WHERE username = ?', ['admin'])->fetchOne();
                $adminExists = $adminCount > 0;
            } catch (\Exception $e) {
                // Si erreur, essayer de créer un admin de secours
                try {
                    $this->createEmergencyAdmin();
                    $adminExists = true;
                } catch (\Exception $e2) {
                    error_log('Échec création admin de secours: ' . $e2->getMessage());
                }
            }
            
            $adminStatus = $adminExists ? '✅ Admin disponible' : '❌ Pas d\'admin trouvé';
            
            return new Response('
                <h1>✅ Reset complet réussi !</h1>
                <p>La base de données a été complètement recréée ' . $fixturesResult . '.</p>
                <p>' . $adminStatus . '</p>
                <p><strong>Login:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
                <a href="/login" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔑 Se connecter</a>
                ' . $errorInfo . '
            ');
            
        } catch (\Exception $e) {
            return new Response('Erreur: ' . $e->getMessage(), 500);
        }
    }

    private function createEmergencyAdmin(): void
    {
        try {
            // 1. Créer une entreprise de base
            try {
                $this->connection->executeStatement("
                    INSERT INTO entreprise (nom, email, telephone, adresse) 
                    VALUES ('Amicale RCA', 'contact@amicale-rca.fr', '', '')
                ");
            } catch (\Exception $e) {
                // L'entreprise existe déjà, on continue
            }
            
            // 2. Créer une personne pour l'admin  
            try {
                $this->connection->executeStatement("
                    INSERT INTO personne (nom, prenom, email, telephone, adresse) 
                    VALUES ('Admin', 'Système', 'admin@amicale-rca.fr', '', '')
                ");
            } catch (\Exception $e) {
                // La personne existe déjà, on continue
            }
            
            // 3. Récupérer l'ID de la personne
            $personneId = $this->connection->executeQuery("
                SELECT id_personne FROM personne WHERE email = 'admin@amicale-rca.fr' LIMIT 1
            ")->fetchOne();
            
            if (!$personneId) {
                throw new \Exception('Impossible de créer/trouver la personne admin');
            }
            
            // 4. Supprimer l'ancien admin s'il existe
            $this->connection->executeStatement('DELETE FROM \"user\" WHERE username = ?', ['admin']);
            
            // 5. Créer le nouvel utilisateur admin
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $this->connection->executeStatement("
                INSERT INTO \"user\" (id_personne, username, password, enabled) 
                VALUES (?, 'admin', ?, true)
            ", [$personneId, $hashedPassword]);
            
            error_log('✅ Admin de secours créé avec succès');
            
        } catch (\Exception $e) {
            error_log('❌ Erreur création admin de secours: ' . $e->getMessage());
            throw $e; // Re-lancer pour debugging
        }
    }

    private function getDuplicates(): array
    {
        $sql = "
            SELECT 
                MIN(id_exercice) as keep_id,
                numero_ordre,
                libelle,
                COUNT(*) as duplicate_count
            FROM exercice 
            GROUP BY numero_ordre, libelle 
            HAVING COUNT(*) > 1
            ORDER BY numero_ordre
        ";

        return $this->connection->executeQuery($sql)->fetchAllAssociative();
    }

    private function createBasicData(): array
    {
        try {
            // 0. Nettoyer les données existantes (SQLite compatible)
            $this->connection->executeStatement('DELETE FROM `transaction`');
            $this->connection->executeStatement('DELETE FROM user_role');
            $this->connection->executeStatement('DELETE FROM role_permission');
            $this->connection->executeStatement('DELETE FROM `user`');
            $this->connection->executeStatement('DELETE FROM personne');
            $this->connection->executeStatement('DELETE FROM exercice');
            $this->connection->executeStatement('DELETE FROM type_transaction');
            $this->connection->executeStatement('DELETE FROM mode_de_paiement');
            $this->connection->executeStatement('DELETE FROM role');
            $this->connection->executeStatement('DELETE FROM permission');
            $this->connection->executeStatement('DELETE FROM entreprise');
            
            // 1. Créer l'entreprise
            $this->connection->executeStatement("
                INSERT INTO entreprise (nom_entreprise, email, telephone, ville, code_postal) 
                VALUES ('Amicale RCA', 'contact@amicale-rca.fr', 123456789, 'Paris', 75001)
            ");
            error_log('✅ Entreprise créée');

            // 2. Créer des personnes
            $this->connection->executeStatement("
                INSERT INTO personne (nom, prenom, email, telephone, ville) 
                VALUES 
                ('Admin', 'Système', 'admin@amicale-rca.fr', NULL, NULL),
                ('Dupont', 'Jean', 'jean.dupont@email.fr', 612345678, 'Paris'),
                ('Martin', 'Marie', 'marie.martin@email.fr', 789012345, 'Lyon')
            ");

            // 3. Créer TOUTES les permissions pour toutes les entités
            $this->connection->executeStatement("
                INSERT INTO permission (name, route, description, public_access) VALUES
                -- Permissions générales
                ('HOME_ACCESS', 'app_home', 'Accès à l''accueil', 1),
                ('LOGIN_ACCESS', 'app_login', 'Accès à la connexion', 1),
                
                -- Entreprises
                ('ENTREPRISE_VIEW', 'app_entreprise_index', 'Voir les entreprises', 0),
                ('ENTREPRISE_CREATE', 'app_entreprise_new', 'Créer une entreprise', 0),
                ('ENTREPRISE_EDIT', 'app_entreprise_edit', 'Modifier une entreprise', 0),
                ('ENTREPRISE_DELETE', 'app_entreprise_delete', 'Supprimer une entreprise', 0),
                ('ENTREPRISE_SHOW', 'app_entreprise_show', 'Voir le détail d''une entreprise', 0),
                
                -- Exercices  
                ('EXERCICE_VIEW', 'app_exercice_index', 'Voir les exercices', 0),
                ('EXERCICE_CREATE', 'app_exercice_new', 'Créer un exercice', 0),
                ('EXERCICE_EDIT', 'app_exercice_edit', 'Modifier un exercice', 0),
                ('EXERCICE_DELETE', 'app_exercice_delete', 'Supprimer un exercice', 0),
                ('EXERCICE_SHOW', 'app_exercice_show', 'Voir le détail d''un exercice', 0),
                
                -- Transactions
                ('TRANSACTION_VIEW', 'app_transaction_index', 'Voir les transactions', 0),
                ('TRANSACTION_CREATE', 'app_transaction_new', 'Créer une transaction', 0),
                ('TRANSACTION_EDIT', 'app_transaction_edit', 'Modifier une transaction', 0),
                ('TRANSACTION_DELETE', 'app_transaction_delete', 'Supprimer une transaction', 0),
                ('TRANSACTION_SHOW', 'app_transaction_show', 'Voir le détail d''une transaction', 0),
                
                -- Personnes
                ('PERSONNE_VIEW', 'app_personne_index', 'Voir les personnes', 0),
                ('PERSONNE_CREATE', 'app_personne_new', 'Créer une personne', 0),
                ('PERSONNE_EDIT', 'app_personne_edit', 'Modifier une personne', 0),
                ('PERSONNE_DELETE', 'app_personne_delete', 'Supprimer une personne', 0),
                ('PERSONNE_SHOW', 'app_personne_show', 'Voir le détail d''une personne', 0),
                
                -- Utilisateurs
                ('USER_VIEW', 'app_user_index', 'Voir les utilisateurs', 0),
                ('USER_CREATE', 'app_user_new', 'Créer un utilisateur', 0),
                ('USER_EDIT', 'app_user_edit', 'Modifier un utilisateur', 0),
                ('USER_DELETE', 'app_user_delete', 'Supprimer un utilisateur', 0),
                ('USER_SHOW', 'app_user_show', 'Voir le détail d''un utilisateur', 0),
                
                -- Rôles
                ('ROLE_VIEW', 'app_role_index', 'Voir les rôles', 0),
                ('ROLE_CREATE', 'app_role_new', 'Créer un rôle', 0),
                ('ROLE_EDIT', 'app_role_edit', 'Modifier un rôle', 0),
                ('ROLE_DELETE', 'app_role_delete', 'Supprimer un rôle', 0),
                ('ROLE_SHOW', 'app_role_show', 'Voir le détail d''un rôle', 0),
                
                -- Permissions
                ('PERMISSION_VIEW', 'app_permission_index', 'Voir les permissions', 0),
                ('PERMISSION_MANAGE', 'app_permission_manage', 'Gérer les permissions', 0),
                
                -- Types de transaction
                ('TYPE_TRANSACTION_VIEW', 'app_type_transaction_index', 'Voir les types de transaction', 0),
                ('TYPE_TRANSACTION_CREATE', 'app_type_transaction_new', 'Créer un type de transaction', 0),
                ('TYPE_TRANSACTION_EDIT', 'app_type_transaction_edit', 'Modifier un type de transaction', 0),
                ('TYPE_TRANSACTION_DELETE', 'app_type_transaction_delete', 'Supprimer un type de transaction', 0),
                
                -- Modes de paiement
                ('MODE_PAIEMENT_VIEW', 'app_mode_de_paiement_index', 'Voir les modes de paiement', 0),
                ('MODE_PAIEMENT_CREATE', 'app_mode_de_paiement_new', 'Créer un mode de paiement', 0),
                ('MODE_PAIEMENT_EDIT', 'app_mode_de_paiement_edit', 'Modifier un mode de paiement', 0),
                ('MODE_PAIEMENT_DELETE', 'app_mode_de_paiement_delete', 'Supprimer un mode de paiement', 0),
                
                -- Administration
                ('ADMIN_ACCESS', 'maintenance_database_admin', 'Accès à l''administration', 0)
            ");

            $this->connection->executeStatement("
                INSERT INTO role (libelle, description, hierarchy_level) VALUES
                ('Administrateur', 'Accès complet au système', 100),
                ('Utilisateur', 'Accès limité aux fonctionnalités', 50)
            ");

            // 4. Créer les modes de paiement
            $this->connection->executeStatement("
                INSERT INTO mode_de_paiement (libelle) VALUES
                ('Espèces'),
                ('Chèque'),
                ('Virement'),
                ('Carte bancaire')
            ");

            // 5. Créer les types de transaction
            $this->connection->executeStatement("
                INSERT INTO type_transaction (libelle, type_montant_autorise) VALUES
                ('Cotisation', 'credit'),
                ('Repas amicale', 'credit'),
                ('Achats matériel', 'debit'),
                ('Frais bancaires', 'debit')
            ");

            // 6. Créer des exercices
            $this->connection->executeStatement("
                INSERT INTO exercice (numero_ordre, libelle, date_debut, date_fin, clos) VALUES
                (2022, 'Exercice 2022-2023', '2022-09-01', '2023-08-31', true),
                (2023, 'Exercice 2023-2024', '2023-09-01', '2024-08-31', true),
                (2024, 'Exercice 2024-2025', '2024-09-01', '2025-08-31', false)
            ");

            // 7. Créer l'utilisateur admin avec mot de passe
            $adminPersonneId = $this->connection->executeQuery("SELECT id_personne FROM personne WHERE email = 'admin@amicale-rca.fr'")->fetchOne();
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            
            // Supprimer l'ancien admin s'il existe
            $this->connection->executeStatement('DELETE FROM `user` WHERE username = ?', ['admin']);
            
            // Créer le nouvel admin
            $this->connection->executeStatement("
                INSERT INTO `user` (id_personne, username, password, enabled) 
                VALUES (?, 'admin', ?, 1)
            ", [$adminPersonneId, $hashedPassword]);
            error_log('✅ Utilisateur admin créé');

            // 8. Assigner le rôle Administrateur à l'admin
            $adminUserId = $this->connection->executeQuery('SELECT id_user FROM `user` WHERE username = ?', ['admin'])->fetchOne();
            $adminRoleId = $this->connection->executeQuery('SELECT id_role FROM role WHERE libelle = ?', ['Administrateur'])->fetchOne();
            
            if ($adminUserId && $adminRoleId) {
                $this->connection->executeStatement("
                    INSERT INTO user_role (user_id, role_id) 
                    VALUES (?, ?)
                ", [$adminUserId, $adminRoleId]);
                error_log('✅ Rôle admin assigné');
            }

            // 9. Assigner toutes les permissions au rôle Administrateur
            $permissions = $this->connection->executeQuery('SELECT id FROM permission')->fetchAllAssociative();
            foreach ($permissions as $permission) {
                $this->connection->executeStatement("
                    INSERT INTO role_permission (role_id, permission_id) 
                    VALUES (?, ?)
                ", [$adminRoleId, $permission['id']]);
            }
            error_log('✅ Permissions assignées');

            error_log('✅ Toutes les données créées avec succès');
            return ['success' => true, 'error' => ''];
        } catch (\Exception $e) {
            $errorMsg = 'Erreur création données de base: ' . $e->getMessage();
            error_log($errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }
    }
}