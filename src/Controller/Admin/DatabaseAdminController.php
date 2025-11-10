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
            // Pour PostgreSQL, on utilise TRUNCATE CASCADE pour supprimer les données
            // tout en respectant les contraintes de clés étrangères
            
            // Pour PostgreSQL géré (sans privilèges SUPERUSER), on utilise DELETE avec ordre correct
            $tablesToDelete = [
                'transaction',
                'user_role', 
                'role_permission',
                'attestation_fiscale',
                'historique_cloture',
                'exercice',
                'type_transaction',
                'mode_de_paiement',
                'personne',
                '"user"', // Quotes car "user" est un mot réservé PostgreSQL
                'role',
                'permission',
                'entreprise'
            ];
            
            // Supprimer dans l'ordre inverse des dépendances (sans privilèges SUPERUSER requis)
            foreach ($tablesToDelete as $tableName) {
                try {
                    // Essayer TRUNCATE CASCADE d'abord (plus efficace)
                    $this->connection->executeStatement("TRUNCATE TABLE $tableName CASCADE");
                } catch (\Exception $e) {
                    // Fallback avec DELETE si TRUNCATE échoue ou permissions insuffisantes
                    try {
                        $this->connection->executeStatement("DELETE FROM $tableName");
                    } catch (\Exception $e2) {
                        // Ignorer les erreurs pour les tables qui n'existent pas ou contraintes
                        error_log("Erreur suppression $tableName: " . $e2->getMessage());
                    }
                }
            }
            
            // Exécuter les fixtures
            $process = Process::fromShellCommandline('php bin/console doctrine:fixtures:load --no-interaction', getcwd());
            $process->run();
            
            if ($process->isSuccessful()) {
                return new Response('
                    <h1>✅ Reset complet réussi !</h1>
                    <p>La base de données a été complètement réinitialisée avec les données par défaut.</p>
                    <p><strong>Login:</strong> admin</p>
                    <p><strong>Password:</strong> admin123</p>
                    <a href="/login" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔑 Se connecter</a>
                ');
            } else {
                return new Response('Erreur lors du reset: ' . $process->getErrorOutput(), 500);
            }
            
        } catch (\Exception $e) {
            return new Response('Erreur: ' . $e->getMessage(), 500);
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
}