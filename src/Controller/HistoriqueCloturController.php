<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Entity\HistoriqueCloture;
use App\Repository\ExerciceRepository;
use App\Repository\HistoriqueCloturRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/historique-cloture')]
class HistoriqueCloturController extends AbstractController
{
    #[Route('/cloturer/{id_exercice}', name: 'app_historique_cloture_cloturer', methods: ['POST'])]
    public function cloturer(
        int $id_exercice,
        Request $request,
        ExerciceRepository $exerciceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Exercice non trouvé'
            ], 404);
        }

        if ($exercice->isClos()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet exercice est déjà clôturé'
            ], 400);
        }

        try {
            // Marquer l'exercice comme clos
            $exercice->setClos(true);

            // Créer l'entrée dans l'historique
            $historique = new HistoriqueCloture();
            $historique->setExercice($exercice);
            $historique->setDateAction(new \DateTime());
            $historique->setTypeAction('CLOTURE');
            
            // Récupérer le commentaire depuis la requête
            $data = json_decode($request->getContent(), true);
            if (isset($data['commentaire'])) {
                $historique->setCommentaire($data['commentaire']);
            }

            // TODO: Gérer l'utilisateur connecté quand l'authentification sera mise en place
            // $historique->setUser($this->getUser());

            $entityManager->persist($historique);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Exercice clôturé avec succès',
                'exercice' => [
                    'id' => $exercice->getIdExercice(),
                    'clos' => $exercice->isClos(),
                    'date_cloture' => $historique->getDateAction()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la clôture: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/decloturer/{id_exercice}', name: 'app_historique_cloture_decloturer', methods: ['POST'])]
    public function decloturer(
        int $id_exercice,
        Request $request,
        ExerciceRepository $exerciceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Exercice non trouvé'
            ], 404);
        }

        if (!$exercice->isClos()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet exercice n\'est pas clôturé'
            ], 400);
        }

        try {
            // Marquer l'exercice comme ouvert
            $exercice->setClos(false);

            // Créer l'entrée dans l'historique
            $historique = new HistoriqueCloture();
            $historique->setExercice($exercice);
            $historique->setDateAction(new \DateTime());
            $historique->setTypeAction('DECLOTURE');
            
            // Récupérer le commentaire depuis la requête
            $data = json_decode($request->getContent(), true);
            if (isset($data['commentaire'])) {
                $historique->setCommentaire($data['commentaire']);
            }

            // TODO: Gérer l'utilisateur connecté quand l'authentification sera mise en place
            // $historique->setUser($this->getUser());

            $entityManager->persist($historique);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Exercice déclôturé avec succès',
                'exercice' => [
                    'id' => $exercice->getIdExercice(),
                    'clos' => $exercice->isClos(),
                    'date_decloture' => $historique->getDateAction()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la déclôture: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/historique/{id_exercice}', name: 'app_historique_cloture_show', methods: ['GET'])]
    public function showHistorique(
        int $id_exercice,
        ExerciceRepository $exerciceRepository,
        HistoriqueCloturRepository $historiqueCloturRepository,
        Request $request
    ): Response {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            throw $this->createNotFoundException('Exercice non trouvé');
        }

        // Si c'est une requête AJAX, retourner du JSON
        if ($request->isXmlHttpRequest()) {
            $historiques = $historiqueCloturRepository->findByExerciceOrderByDate($id_exercice);

            $data = [];
            foreach ($historiques as $historique) {
                $data[] = [
                    'id' => $historique->getIdHistorique(),
                    'date_action' => $historique->getDateAction()->format('Y-m-d H:i:s'),
                    'type_action' => $historique->getTypeAction(),
                    'commentaire' => $historique->getCommentaire(),
                    'user' => $historique->getUser() ? $historique->getUser()->getUsername() : null
                ];
            }

            return new JsonResponse([
                'success' => true,
                'exercice' => [
                    'id' => $exercice->getIdExercice(),
                    'libelle' => $exercice->getLibelle(),
                    'clos' => $exercice->isClos()
                ],
                'historiques' => $data
            ]);
        }

        // Sinon retourner la vue HTML
        $historiques = $historiqueCloturRepository->findByExerciceOrderByDate($id_exercice);
        
        return $this->render('historique_cloture/show.html.twig', [
            'exercice' => $exercice,
            'historiques' => $historiques,
        ]);
    }

    #[Route('/historique-recent', name: 'app_historique_cloture_recent', methods: ['GET'])]
    public function getHistoriqueRecent(
        HistoriqueCloturRepository $historiqueCloturRepository
    ): JsonResponse {
        // Récupérer les 10 dernières actions de clôture/déclôture
        $historiques = $historiqueCloturRepository->findRecentHistorique(10);

        $data = [];
        foreach ($historiques as $historique) {
            $data[] = [
                'id' => $historique->getIdHistorique(),
                'date_action' => $historique->getDateAction()->format('Y-m-d H:i:s'),
                'type_action' => $historique->getTypeAction(),
                'commentaire' => $historique->getCommentaire(),
                'user' => $historique->getUser() ? $historique->getUser()->getUsername() : null,
                'exercice_id' => $historique->getExercice()->getIdExercice(),
                'exercice_libelle' => $historique->getExercice()->getLibelle()
            ];
        }

        return new JsonResponse([
            'success' => true,
            'historiques' => $data
        ]);
    }
}