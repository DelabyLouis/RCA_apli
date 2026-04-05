<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PersonneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/exercer-mes-droits', name: 'app_exercise_rights_submit', methods: ['POST'])]
    public function submitRightsRequest(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $requestType = $request->request->get('requestType');
        $description = $request->request->get('description');
        $contactMethod = $request->request->get('contactMethod', 'email');

        // Validation
        if (empty($requestType)) {
            return new JsonResponse(['success' => false, 'message' => 'Type de demande requis'], 400);
        }

        if (empty(trim($description))) {
            return new JsonResponse(['success' => false, 'message' => 'Description de la demande requise'], 400);
        }

        // Types de demandes autorisés pour usage interne (simplifiés)
        $allowedTypes = ['access', 'rectification', 'deletion'];
        if (!in_array($requestType, $allowedTypes)) {
            return new JsonResponse(['success' => false, 'message' => 'Type de demande non valide'], 400);
        }

        try {
            // Ici, vous pourriez enregistrer la demande dans une table dédiée
            // Pour l'instant, on simule un traitement réussi
            
            // Génération d'un numéro de demande unique
            $requestNumber = 'RCA-' . date('Ymd') . '-' . substr(uniqid(), -6);
            
            // Dans une vraie application, vous devriez :
            // 1. Enregistrer la demande dans la base de données
            // 2. Envoyer un email de confirmation à l'utilisateur
            // 3. Notifier les administrateurs
            // 4. Créer un système de suivi des demandes
            
            return new JsonResponse([
                'success' => true, 
                'message' => 'Votre demande a été enregistrée avec succès !',
                'requestNumber' => $requestNumber,
                'expectedResponseTime' => 'Dans un délai maximum d\'un mois'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
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

    #[Route('/mes-donnees/update', name: 'app_my_data_update', methods: ['POST'])]
    public function updateMyData(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $personne = $user->getPersonne();
        if (!$personne) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune personne associée à ce compte'], 404);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'nom':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le nom ne peut pas être vide'], 400);
                    }
                    $personne->setNom(trim($value));
                    break;
                case 'prenom':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le prénom ne peut pas être vide'], 400);
                    }
                    $personne->setPrenom(trim($value));
                    break;
                case 'civilite':
                    $personne->setCivilite($value ? trim($value) : null);
                    break;
                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return new JsonResponse(['success' => false, 'message' => 'Email invalide'], 400);
                    }
                    $personne->setEmail($value ? trim($value) : null);
                    break;
                case 'telephone':
                    if (!empty($value) && !is_numeric($value)) {
                        return new JsonResponse(['success' => false, 'message' => 'Le téléphone doit être numérique'], 400);
                    }
                    $personne->setTelephone($value ? trim($value) : null);
                    break;
                case 'numero_voie':
                    $personne->setNumeroVoie($value ? trim($value) : null);
                    break;
                case 'rue':
                    $personne->setRue($value ? trim($value) : null);
                    break;
                case 'complement_adresse':
                    $personne->setComplementAdresse($value ? trim($value) : null);
                    break;
                case 'code_postal':
                    if (!empty($value) && !preg_match('/^\d{5}$/', $value)) {
                        return new JsonResponse(['success' => false, 'message' => 'Le code postal doit contenir 5 chiffres'], 400);
                    }
                    $personne->setCodePostal($value ? trim($value) : null);
                    break;
                case 'ville':
                    $personne->setVille($value ? trim($value) : null);
                    break;
                case 'pays':
                    $personne->setPays($value ? trim($value) : 'France');
                    break;
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Champ non autorisé'], 400);
            }

            $entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Données mises à jour avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()], 500);
        }
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