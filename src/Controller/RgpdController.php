<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RgpdController extends AbstractController
{
    #[Route('/politique-confidentialite', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('rgpd/privacy_policy.html.twig');
    }

    #[Route('/mentions-legales', name: 'app_legal_mentions')]
    public function legalMentions(): Response
    {
        return $this->render('rgpd/legal_mentions.html.twig');
    }

    #[Route('/mes-donnees', name: 'app_my_data')]
    public function myData(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('rgpd/my_data.html.twig', [
            'user' => $user,
            'personne' => $user->getPersonne()
        ]);
    }

    #[Route('/exercer-mes-droits', name: 'app_exercise_rights')]
    public function exerciseRights(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('rgpd/exercise_rights.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/export-donnees/{format}', name: 'app_export_data', methods: ['GET'], requirements: ['format' => 'json|csv|pdf'])]
    public function exportData(string $format): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $personne = $user->getPersonne();
        
        // Préparer les données à exporter
        $userData = [
            'utilisateur' => [
                'nom_utilisateur' => $user->getUsername(),
                'roles' => array_map(fn($role) => $role->getLibelle(), $user->getUserRoles()->toArray()),
                'date_creation' => null // À ajouter si disponible
            ],
            'informations_personnelles' => []
        ];

        if ($personne) {
            $userData['informations_personnelles'] = [
                'civilite' => $personne->getCivilite(),
                'nom' => $personne->getNom(),
                'prenom' => $personne->getPrenom(),
                'email' => $personne->getEmail(),
                'telephone' => $personne->getTelephone(),
                'adresse' => [
                    'numero_voie' => $personne->getNumeroVoie(),
                    'rue' => $personne->getRue(),
                    'complement' => $personne->getComplementAdresse(),
                    'code_postal' => $personne->getCodePostal(),
                    'ville' => $personne->getVille(),
                    'pays' => $personne->getPays()
                ]
            ];
        }

        // TODO: Ajouter les données de transactions, consentements, etc.

        switch ($format) {
            case 'json':
                $response = new Response(json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set('Content-Disposition', 'attachment; filename="mes_donnees_rca.json"');
                break;
                
            case 'csv':
                // Conversion JSON vers CSV (simplifié)
                $csv = "Type,Champ,Valeur\n";
                $this->arrayToCsv($userData, $csv);
                
                $response = new Response($csv);
                $response->headers->set('Content-Type', 'text/csv');
                $response->headers->set('Content-Disposition', 'attachment; filename="mes_donnees_rca.csv"');
                break;
                
            case 'pdf':
                // TODO: Implémenter l'export PDF
                throw new \Exception('Export PDF non encore implémenté');
                
            default:
                throw new \InvalidArgumentException('Format non supporté');
        }

        return $response;
    }

    private function arrayToCsv(array $data, string &$csv, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $this->arrayToCsv($value, $csv, $fullKey);
            } else {
                $csv .= '"' . $fullKey . '","' . $key . '","' . ($value ?? '') . '"' . "\n";
            }
        }
    }
}