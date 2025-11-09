<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugDeleteController extends AbstractController
{
    #[Route('/debug-delete', name: 'debug_delete')]
    public function index(TransactionRepository $transactionRepository): Response
    {
        $transactions = $transactionRepository->findAll();
        
        $html = '<h1>Debug Suppression</h1>';
        $html .= '<p>Test des boutons de suppression</p>';
        
        foreach (array_slice($transactions, 0, 3) as $transaction) {
            $html .= '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px;">';
            $html .= '<p><strong>Transaction ID:</strong> ' . $transaction->getIdTransaction() . '</p>';
            $html .= '<p><strong>Libellé:</strong> ' . htmlspecialchars($transaction->getLibelle()) . '</p>';
            $html .= '<button class="btn btn-danger delete-btn" data-id="' . $transaction->getIdTransaction() . '">Supprimer (Test)</button>';
            $html .= '</div>';
        }
        
        $html .= '<script>
        document.querySelectorAll(".delete-btn").forEach(function(button) {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                console.log("Bouton cliqué pour transaction:", this.dataset.id);
                
                if (!confirm("Êtes-vous sûr de vouloir supprimer cette transaction ?")) {
                    return;
                }
                
                const transactionId = this.dataset.id;
                const url = "/transaction/" + transactionId + "/delete-ajax";
                
                console.log("URL appelée:", url);
                
                fetch(url, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => {
                    console.log("Réponse statut:", response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Réponse data:", data);
                    if (data.success) {
                        this.parentElement.remove();
                        alert("Transaction supprimée avec succès!");
                    } else {
                        alert("Erreur: " + (data.error || "Impossible de supprimer"));
                    }
                })
                .catch(error => {
                    console.error("Erreur:", error);
                    alert("Erreur de connexion");
                });
            });
        });
        </script>';
        
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}