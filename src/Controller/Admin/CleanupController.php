<?php

namespace App\Controller\Admin;

use App\Repository\ExerciceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class CleanupController extends AbstractController
{
    public function __construct(
        private ExerciceRepository $exerciceRepository,
        private EntityManagerInterface $entityManager,
        private Connection $connection
    ) {}

    #[Route('/clean-duplicates', name: 'admin_clean_duplicates')]
    public function cleanDuplicates(Request $request): Response
    {
        // Token de sécurité simple
        $expectedToken = 'rca2024cleanup';
        $token = $request->query->get('token');
        
        if ($token !== $expectedToken) {
            return new Response('Accès non autorisé. Token requis.', 403);
        }

        $action = $request->query->get('action', 'preview');
        
        // Compter les exercices avant nettoyage
        $totalBefore = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];

        // Identifier les doublons
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

        $duplicates = $this->connection->executeQuery($sql)->fetchAllAssociative();
        
        if (empty($duplicates)) {
            return new Response('
                <h1>🎉 Aucun doublon trouvé !</h1>
                <p>Total exercices: ' . $totalBefore . '</p>
                <a href="/exercice">← Retour aux exercices</a>
            ');
        }

        if ($action === 'preview') {
            return $this->renderPreview($duplicates, $totalBefore, $expectedToken);
        }

        if ($action === 'execute') {
            return $this->executeCleaning($duplicates, $totalBefore);
        }

        return new Response('Action inconnue', 400);
    }

    private function renderPreview(array $duplicates, int $totalBefore, string $token): Response
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Nettoyage des doublons - Preview</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .duplicate { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin: 5px 0; border-radius: 3px; }
                .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 5px; }
                .btn-danger { background: #dc3545; color: white; }
                .btn-secondary { background: #6c757d; color: white; }
            </style>
        </head>
        <body>
            <h1>🧹 Nettoyage des exercices dupliqués</h1>
            
            <div class="warning">
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

            $html .= '<div class="duplicate">
                <strong>' . htmlspecialchars($libelle) . '</strong> (N°' . $numeroOrdre . ')<br>
                Copies: ' . $duplicateCount . ' | Garder ID: ' . $keepId . ' | Supprimer: ' . $toDeleteCount . '
            </div>';
        }

        $html .= '
            <h2>Résumé:</h2>
            <p>Exercices à supprimer: <strong>' . $totalToDelete . '</strong></p>
            <p>Exercices après nettoyage: <strong>' . ($totalBefore - $totalToDelete) . '</strong></p>

            <div style="margin-top: 30px;">
                <a href="?token=' . $token . '&action=execute" class="btn btn-danger" 
                   onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ' . $totalToDelete . ' exercices ?\')">
                   🗑️ Supprimer les doublons
                </a>
                <a href="/exercice" class="btn btn-secondary">← Annuler</a>
            </div>
        </body>
        </html>';

        return new Response($html);
    }

    private function executeCleaning(array $duplicates, int $totalBefore): Response
    {
        $totalDeleted = 0;
        $results = [];

        foreach ($duplicates as $duplicate) {
            $keepId = $duplicate['keep_id'];
            $numeroOrdre = $duplicate['numero_ordre'];
            $libelle = $duplicate['libelle'];
            $duplicateCount = $duplicate['duplicate_count'];

            // Supprimer tous les doublons sauf celui avec le plus petit ID
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
            $results[] = [
                'libelle' => $libelle,
                'numero_ordre' => $numeroOrdre,
                'deleted' => $deletedCount,
                'kept_id' => $keepId
            ];
        }

        // Compter après nettoyage
        $totalAfter = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];
        
        // Clear entity manager cache
        $this->entityManager->clear();

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Nettoyage terminé</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                .success { background: #d1ecf1; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .result { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin: 5px 0; border-radius: 3px; }
                .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 5px; background: #007bff; color: white; }
            </style>
        </head>
        <body>
            <h1>✅ Nettoyage terminé avec succès !</h1>
            
            <div class="success">
                <strong>Résumé:</strong><br>
                Exercices avant: ' . $totalBefore . '<br>
                Exercices après: ' . $totalAfter . '<br>
                Exercices supprimés: ' . $totalDeleted . '
            </div>

            <h2>Détails:</h2>';

        foreach ($results as $result) {
            $html .= '<div class="result">
                ✅ <strong>' . htmlspecialchars($result['libelle']) . '</strong> (N°' . $result['numero_ordre'] . ') - ' . $result['deleted'] . ' doublons supprimés, gardé ID ' . $result['kept_id'] . '
            </div>';
        }

        $html .= '
            <div style="margin-top: 30px;">
                <a href="/exercice" class="btn">🎉 Voir les exercices nettoyés</a>
            </div>
        </body>
        </html>';

        return new Response($html);
    }
}