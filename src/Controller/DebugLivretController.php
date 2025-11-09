<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugLivretController extends AbstractController
{
    #[Route('/debug-livret', name: 'debug_livret')]
    public function index(TransactionRepository $transactionRepository): Response
    {
        $transactions = $transactionRepository->createQueryBuilder('t')
            ->where('t.type_compte = :type')
            ->setParameter('type', 'livret')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
        
        $html = '<h1>Debug Suppression Livret</h1>';
        $html .= '<p>Test des boutons de suppression du livret</p>';
        
        if (empty($transactions)) {
            $html .= '<p style="color: orange;">⚠️ Aucune transaction du livret trouvée dans la base.</p>';
            return new Response($html, 200, ['Content-Type' => 'text/html']);
        }
        
        foreach ($transactions as $transaction) {
            $html .= '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px;">';
            $html .= '<p><strong>Transaction ID:</strong> ' . $transaction->getIdTransaction() . '</p>';
            $html .= '<p><strong>Libellé:</strong> ' . htmlspecialchars($transaction->getLibelle()) . '</p>';
            $html .= '<p><strong>Type compte:</strong> ' . $transaction->getTypeCompte() . '</p>';
            $html .= '<p><strong>Montant:</strong> ' . $transaction->getMontant() . '€</p>';
            $html .= '<button class="btn btn-danger delete-btn" data-id="' . $transaction->getIdTransaction() . '">Supprimer Livret (Test)</button>';
            $html .= '</div>';
        }
        
        $html .= '<script>
        document.querySelectorAll(".delete-btn").forEach(function(button) {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                console.log("🚀 Bouton livret cliqué pour transaction:", this.dataset.id);
                
                if (!confirm("Êtes-vous sûr de vouloir supprimer cette transaction du livret ?")) {
                    return;
                }
                
                const transactionId = this.dataset.id;
                const url = "/livret/" + transactionId + "/delete-ajax";
                
                console.log("🔗 URL appelée:", url);
                
                fetch(url, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => {
                    console.log("📡 Réponse statut:", response.status);
                    console.log("📡 Réponse headers:", response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log("📋 Données reçues:", data);
                    if (data.success) {
                        this.parentElement.style.backgroundColor = "lightgreen";
                        this.textContent = "✅ Supprimé !";
                        alert("Transaction du livret supprimée avec succès!");
                    } else {
                        alert("Erreur: " + (data.error || "Impossible de supprimer"));
                    }
                })
                .catch(error => {
                    console.error("💥 Erreur:", error);
                    alert("Erreur de connexion: " + error.message);
                });
            });
        });
        </script>';
        
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}