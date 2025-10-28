<?php

namespace App\DataFixtures;

use App\Entity\Personne;
use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use App\Entity\ModeDePaiement;
use App\Entity\Exercice;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AttestationFiscaleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer ou créer le type de transaction "cotisation"
        $typeCotisation = $manager->getRepository(TypeTransaction::class)
            ->findOneBy(['libelle' => 'cotisation']);
        
        if (!$typeCotisation) {
            $typeCotisation = new TypeTransaction();
            $typeCotisation->setLibelle('cotisation');
            $typeCotisation->setDescription('Cotisations des membres de l\'association (ouvre droit aux attestations fiscales)');
            $typeCotisation->setTypeMontantAutorise('credit');
            $manager->persist($typeCotisation);
        }

        // Récupérer ou créer des modes de paiement
        $modesPaiement = [];
        $modesPaiementData = [
            'Chèque',
            'Virement',
            'Espèces',
            'Carte bancaire',
            'Prélèvement',
        ];

        foreach ($modesPaiementData as $libelle) {
            $mode = $manager->getRepository(ModeDePaiement::class)
                ->findOneBy(['libelle' => $libelle]);
            
            if (!$mode) {
                $mode = new ModeDePaiement();
                $mode->setLibelle($libelle);
                $manager->persist($mode);
            }
            $modesPaiement[] = $mode;
        }

        // Récupérer ou créer un exercice pour 2024 et 2025
        $exercices = [];
        $exercicesData = [
            ['libelle' => 'Exercice 2024', 'annee' => 2024, 'numero_ordre' => 1],
            ['libelle' => 'Exercice 2025', 'annee' => 2025, 'numero_ordre' => 2],
        ];

        foreach ($exercicesData as $exerciceData) {
            $exercice = $manager->getRepository(Exercice::class)
                ->findOneBy(['libelle' => $exerciceData['libelle']]);
            
            if (!$exercice) {
                $exercice = new Exercice();
                $exercice->setLibelle($exerciceData['libelle']);
                $exercice->setDateDebut(new \DateTime($exerciceData['annee'] . '-01-01'));
                $exercice->setDateFin(new \DateTime($exerciceData['annee'] . '-12-31'));
                $exercice->setNumeroOrdre($exerciceData['numero_ordre']);
                $exercice->setClos(false);
                $manager->persist($exercice);
            }
            $exercices[] = $exercice;
        }

        // Créer des personnes avec des profils variés
        $personnes = [];
        $personnesData = [
            [
                'nom' => 'Martin',
                'prenom' => 'Jean',
                'email' => 'jean.martin@email.com',
                'numero_voie' => '15',
                'rue' => 'Rue de la Paix',
                'ville' => 'Saint-Omer',
                'code_postal' => 62500,
                'telephone' => 321654987,
            ],
            [
                'nom' => 'Dubois',
                'prenom' => 'Marie',
                'email' => 'marie.dubois@email.com',
                'numero_voie' => '8',
                'rue' => 'Avenue Victor Hugo',
                'ville' => 'Saint-Omer',
                'code_postal' => 62500,
                'telephone' => 654987321,
            ],
            [
                'nom' => 'Moreau',
                'prenom' => 'Pierre',
                'email' => 'pierre.moreau@email.com',
                'numero_voie' => '23',
                'rue' => 'Boulevard de la République',
                'ville' => 'Arques',
                'code_postal' => 62510,
                'telephone' => 987321654,
            ],
            [
                'nom' => 'Leroy',
                'prenom' => 'Sophie',
                'email' => 'sophie.leroy@email.com',
                'numero_voie' => '42',
                'rue' => 'Rue du Commerce',
                'ville' => 'Blendecques',
                'code_postal' => 62575,
                'telephone' => 456789123,
            ],
            [
                'nom' => 'Lefebvre',
                'prenom' => 'Antoine',
                'email' => 'antoine.lefebvre@email.com',
                'numero_voie' => '7',
                'rue' => 'Place du Général de Gaulle',
                'ville' => 'Saint-Omer',
                'code_postal' => 62500,
                'telephone' => 789123456,
            ],
            [
                'nom' => 'Roux',
                'prenom' => 'Isabelle',
                'email' => 'isabelle.roux@email.com',
                'numero_voie' => '33',
                'rue' => 'Rue des Sports',
                'ville' => 'Longuenesse',
                'code_postal' => 62219,
                'telephone' => 123456789,
            ],
            [
                'nom' => 'Fournier',
                'prenom' => 'Laurent',
                'email' => 'laurent.fournier@email.com',
                'numero_voie' => '91',
                'rue' => 'Rue de la Gare',
                'ville' => 'Saint-Omer',
                'code_postal' => 62500,
                'telephone' => 147258369,
            ],
            [
                'nom' => 'Girard',
                'prenom' => 'Nathalie',
                'email' => 'nathalie.girard@email.com',
                'numero_voie' => '12',
                'rue' => 'Impasse des Tilleuls',
                'ville' => 'Wizernes',
                'code_postal' => 62570,
                'telephone' => 258369147,
            ],
        ];

        foreach ($personnesData as $personneData) {
            // Vérifier si la personne existe déjà
            $existingPersonne = $manager->getRepository(Personne::class)
                ->findOneBy(['nom' => $personneData['nom'], 'prenom' => $personneData['prenom']]);
            
            if (!$existingPersonne) {
                $personne = new Personne();
                $personne->setNom($personneData['nom']);
                $personne->setPrenom($personneData['prenom']);
                $personne->setEmail($personneData['email']);
                $personne->setNumeroVoie($personneData['numero_voie']);
                $personne->setRue($personneData['rue']);
                $personne->setVille($personneData['ville']);
                $personne->setCodePostal($personneData['code_postal']);
                $personne->setTelephone($personneData['telephone']);
                $personne->setPays('France');
                
                $manager->persist($personne);
                $personnes[] = $personne;
            } else {
                $personnes[] = $existingPersonne;
            }
        }

        $manager->flush();

        // Créer des cotisations pour chaque personne sur plusieurs années
        $cotisationsData = [
            // Jean Martin - cotisations régulières
            ['personne' => 0, 'annee' => 2024, 'mois' => 1, 'montant' => 150, 'mode' => 0, 'exercice' => 0],
            ['personne' => 0, 'annee' => 2024, 'mois' => 6, 'montant' => 200, 'mode' => 1, 'exercice' => 0],
            ['personne' => 0, 'annee' => 2025, 'mois' => 2, 'montant' => 180, 'mode' => 0, 'exercice' => 1],

            // Marie Dubois - grosse contributrices
            ['personne' => 1, 'annee' => 2024, 'mois' => 3, 'montant' => 300, 'mode' => 1, 'exercice' => 0],
            ['personne' => 1, 'annee' => 2024, 'mois' => 9, 'montant' => 250, 'mode' => 2, 'exercice' => 0],
            ['personne' => 1, 'annee' => 2025, 'mois' => 1, 'montant' => 320, 'mode' => 1, 'exercice' => 1],
            ['personne' => 1, 'annee' => 2025, 'mois' => 7, 'montant' => 275, 'mode' => 4, 'exercice' => 1],

            // Pierre Moreau - cotisations modestes
            ['personne' => 2, 'annee' => 2024, 'mois' => 5, 'montant' => 80, 'mode' => 2, 'exercice' => 0],
            ['personne' => 2, 'annee' => 2025, 'mois' => 4, 'montant' => 90, 'mode' => 2, 'exercice' => 1],

            // Sophie Leroy - cotisations par carte
            ['personne' => 3, 'annee' => 2024, 'mois' => 2, 'montant' => 120, 'mode' => 3, 'exercice' => 0],
            ['personne' => 3, 'annee' => 2024, 'mois' => 8, 'montant' => 135, 'mode' => 3, 'exercice' => 0],
            ['personne' => 3, 'annee' => 2025, 'mois' => 3, 'montant' => 140, 'mode' => 3, 'exercice' => 1],

            // Antoine Lefebvre - prélèvements automatiques
            ['personne' => 4, 'annee' => 2024, 'mois' => 1, 'montant' => 200, 'mode' => 4, 'exercice' => 0],
            ['personne' => 4, 'annee' => 2024, 'mois' => 7, 'montant' => 200, 'mode' => 4, 'exercice' => 0],
            ['personne' => 4, 'annee' => 2025, 'mois' => 1, 'montant' => 210, 'mode' => 4, 'exercice' => 1],
            ['personne' => 4, 'annee' => 2025, 'mois' => 7, 'montant' => 210, 'mode' => 4, 'exercice' => 1],

            // Isabelle Roux - cotisations irrégulières
            ['personne' => 5, 'annee' => 2024, 'mois' => 11, 'montant' => 175, 'mode' => 0, 'exercice' => 0],
            ['personne' => 5, 'annee' => 2025, 'mois' => 9, 'montant' => 185, 'mode' => 1, 'exercice' => 1],

            // Laurent Fournier - nouvelle adhésion en 2025
            ['personne' => 6, 'annee' => 2025, 'mois' => 5, 'montant' => 160, 'mode' => 0, 'exercice' => 1],

            // Nathalie Girard - cotisations multiples
            ['personne' => 7, 'annee' => 2024, 'mois' => 4, 'montant' => 100, 'mode' => 2, 'exercice' => 0],
            ['personne' => 7, 'annee' => 2024, 'mois' => 10, 'montant' => 125, 'mode' => 3, 'exercice' => 0],
            ['personne' => 7, 'annee' => 2025, 'mois' => 6, 'montant' => 130, 'mode' => 1, 'exercice' => 1],
        ];

        foreach ($cotisationsData as $index => $cotisationData) {
            // Générer un libellé unique avec l'index et le nom de la personne
            $personneName = $personnes[$cotisationData['personne']]->getPrenom() . ' ' . $personnes[$cotisationData['personne']]->getNom();
            $libelle = 'Cotisation ' . $cotisationData['annee'] . ' - ' . $personneName . ' - ' . sprintf('%02d', $cotisationData['mois']);
            
            // Vérifier si cette transaction existe déjà
            $existingTransaction = $manager->getRepository(Transaction::class)
                ->findOneBy(['libelle' => $libelle]);
            
            if (!$existingTransaction) {
                $transaction = new Transaction();
                $transaction->setLibelle($libelle);
                $transaction->setDateTransaction(new \DateTime($cotisationData['annee'] . '-' . sprintf('%02d', $cotisationData['mois']) . '-15'));
                $transaction->setMontant((string) $cotisationData['montant']);
                $transaction->setTypeTransaction($typeCotisation);
                $transaction->setPersonne($personnes[$cotisationData['personne']]);
                $transaction->setModeDePaiement($modesPaiement[$cotisationData['mode']]);
                $transaction->setExercice($exercices[$cotisationData['exercice']]);
                $transaction->setTypeCompte('compte_courant');
                
                // Générer un numéro d'ordre unique
                $numeroOrdre = ($cotisationData['annee'] * 1000) + ($cotisationData['mois'] * 10) + $cotisationData['personne'] + $index + 1;
                $transaction->setNumeroOrdre($numeroOrdre);
                
                $manager->persist($transaction);
            }
        }

        $manager->flush();
    }
}