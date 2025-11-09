<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Entity\TypeTransaction;
use App\Entity\ModeDePaiement;
use App\Entity\Personne;
use App\Entity\Transaction;
use App\Entity\User;
use App\Command\ImportHistoricalDataCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CleanImportController extends AbstractController
{
    #[Route('/clean-and-import', name: 'clean_import')]
    public function cleanAndImport(EntityManagerInterface $entityManager): Response
    {
        $html = '<h1>Nettoyage et réimport des données</h1>';
        
        try {
            // 1. Supprimer toutes les données existantes (sauf admin)
            $html .= '<h2>1. Suppression des données existantes</h2>';
            
            // Supprimer les transactions
            $transactionRepo = $entityManager->getRepository(Transaction::class);
            $transactions = $transactionRepo->findAll();
            foreach ($transactions as $transaction) {
                $entityManager->remove($transaction);
            }
            $html .= '<p>✅ Transactions supprimées: ' . count($transactions) . '</p>';
            
            // Supprimer les exercices (sauf si nécessaire pour l'admin)
            $exerciceRepo = $entityManager->getRepository(Exercice::class);
            $exercices = $exerciceRepo->findAll();
            foreach ($exercices as $exercice) {
                $entityManager->remove($exercice);
            }
            $html .= '<p>✅ Exercices supprimés: ' . count($exercices) . '</p>';
            
            // Supprimer les types de transaction
            $typeRepo = $entityManager->getRepository(TypeTransaction::class);
            $types = $typeRepo->findAll();
            foreach ($types as $type) {
                $entityManager->remove($type);
            }
            $html .= '<p>✅ Types de transactions supprimés: ' . count($types) . '</p>';
            
            // Supprimer les modes de paiement
            $modeRepo = $entityManager->getRepository(ModeDePaiement::class);
            $modes = $modeRepo->findAll();
            foreach ($modes as $mode) {
                $entityManager->remove($mode);
            }
            $html .= '<p>✅ Modes de paiement supprimés: ' . count($modes) . '</p>';
            
            // Supprimer les utilisateurs (sauf admin) AVANT les personnes
            $userRepo = $entityManager->getRepository(User::class);
            $users = $userRepo->findAll();
            $deletedUsers = 0;
            foreach ($users as $user) {
                // Ne pas supprimer l'utilisateur admin
                if ($user->getUsername() !== 'admin1' && $user->getUsername() !== 'admin') {
                    $entityManager->remove($user);
                    $deletedUsers++;
                }
            }
            $html .= '<p>✅ Utilisateurs supprimés: ' . $deletedUsers . ' (admin préservé)</p>';
            
            // Supprimer les personnes (sauf admin) APRÈS les utilisateurs
            $personneRepo = $entityManager->getRepository(Personne::class);
            $personnes = $personneRepo->findAll();
            $deleted = 0;
            foreach ($personnes as $personne) {
                // Ne pas supprimer la personne admin
                if ($personne->getNom() !== 'ADMIN') {
                    $entityManager->remove($personne);
                    $deleted++;
                }
            }
            $html .= '<p>✅ Personnes supprimées: ' . $deleted . ' (admin préservé)</p>';
            
            $entityManager->flush();
            $html .= '<p><strong>🗑️ Nettoyage terminé</strong></p>';
            
            // 2. Réimporter les données
            $html .= '<h2>2. Réimport des données historiques</h2>';
            
            $command = new ImportHistoricalDataCommand($entityManager);
            $input = new ArrayInput([]);
            $output = new BufferedOutput();
            
            $result = $command->run($input, $output);
            $outputContent = $output->fetch();
            
            $html .= '<pre>' . htmlspecialchars($outputContent) . '</pre>';
            $html .= '<p><strong>Résultat: ' . ($result === 0 ? '✅ SUCCESS' : '❌ FAILURE') . '</strong></p>';
            
            if ($result === 0) {
                $html .= '<h2>3. Vérification finale</h2>';
                $html .= '<p>✅ <a href="/exercice">Voir les exercices</a></p>';
                $html .= '<p>✅ <a href="/transaction">Voir les transactions</a></p>';
                $html .= '<p>✅ <a href="/personne">Voir les personnes</a></p>';
            }
            
        } catch (\Exception $e) {
            $html .= '<p style="color:red">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
            $html .= '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}