<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Form\ExerciceType;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugExerciceController extends AbstractController
{
    #[Route('/public/debug-exercice', name: 'app_debug_exercice')]
    public function debugExercice(ExerciceRepository $exerciceRepository, EntityManagerInterface $entityManager): Response
    {
        $debug = [];
        
        try {
            // Tester la création d'un exercice de base
            $exercice = new Exercice();
            $debug['exercice_created'] = 'SUCCESS';
            
            // Tester getLastNumeroOrdre
            $lastNumero = $exerciceRepository->getLastNumeroOrdre();
            $debug['last_numero_ordre'] = $lastNumero;
            
            // Tester setDefaultDates
            $this->setDefaultDates($exercice, $exerciceRepository);
            $debug['default_dates_set'] = [
                'date_debut' => $exercice->getDateDebut() ? $exercice->getDateDebut()->format('Y-m-d') : 'null',
                'date_fin' => $exercice->getDateFin() ? $exercice->getDateFin()->format('Y-m-d') : 'null',
                'libelle' => $exercice->getLibelle()
            ];
            
            // Tester la création du formulaire
            $form = $this->createForm(ExerciceType::class, $exercice);
            $debug['form_created'] = 'SUCCESS';
            
            // Tester le rendu du template
            $debug['status'] = 'All tests passed - likely a template or form field issue';
            
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['trace'] = $e->getTraceAsString();
        }
        
        return $this->json($debug, 200, [], ['json_encode_options' => JSON_PRETTY_PRINT]);
    }
    
    private function setDefaultDates(Exercice $exercice, ExerciceRepository $exerciceRepository): void
    {
        // Récupérer le dernier exercice par date de fin
        $lastExercice = $exerciceRepository->createQueryBuilder('e')
            ->orderBy('e.date_fin', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastExercice && $lastExercice->getDateFin()) {
            // Date de début = jour après la date de fin du dernier exercice
            $dateDebut = clone $lastExercice->getDateFin();
            $dateDebut->modify('+1 day');
        } else {
            // Si aucun exercice précédent, commencer au 1er janvier de l'année courante
            $dateDebut = new \DateTime('first day of January this year');
        }

        // Date de fin = un an après la date de début (moins un jour pour finir le 31 décembre)
        $dateFin = clone $dateDebut;
        $dateFin->modify('+1 year -1 day');

        $exercice->setDateDebut($dateDebut);
        $exercice->setDateFin($dateFin);
        
        // Générer un libellé par défaut basé sur l'année
        $annee = $dateDebut->format('Y');
        $exercice->setLibelle("Exercice {$annee}");
    }
}