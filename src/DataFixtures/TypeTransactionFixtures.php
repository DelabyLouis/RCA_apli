<?php

namespace App\DataFixtures;

use App\Entity\TypeTransaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TypeTransactionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Vérifier si le type "cotisation" existe déjà
        $cotisationType = $manager->getRepository(TypeTransaction::class)
            ->findOneBy(['libelle' => 'cotisation']);
            
        if (!$cotisationType) {
            $cotisationType = new TypeTransaction();
            $cotisationType->setLibelle('cotisation');
            $cotisationType->setDescription('Cotisations des membres de l\'association (ouvre droit aux attestations fiscales)');
            $cotisationType->setTypeMontantAutorise('credit'); // Seuls les montants positifs (recettes)
            
            $manager->persist($cotisationType);
        }
        
        // Ajouter d'autres types de transactions utiles pour un club de rugby
        $typesTransactions = [
            [
                'libelle' => 'subvention',
                'description' => 'Subventions reçues de la mairie, conseil départemental, etc.',
                'type_montant' => 'credit'
            ],
            [
                'libelle' => 'sponsoring',
                'description' => 'Sponsoring des entreprises partenaires',
                'type_montant' => 'credit'
            ],
            [
                'libelle' => 'vente',
                'description' => 'Ventes diverses (merchandising, buvette, etc.)',
                'type_montant' => 'credit'
            ],
            [
                'libelle' => 'frais_deplacement',
                'description' => 'Frais de déplacement des équipes',
                'type_montant' => 'debit'
            ],
            [
                'libelle' => 'equipement',
                'description' => 'Achat d\'équipements sportifs',
                'type_montant' => 'debit'
            ],
            [
                'libelle' => 'location_terrain',
                'description' => 'Location de terrain de rugby',
                'type_montant' => 'debit'
            ],
            [
                'libelle' => 'assurance',
                'description' => 'Assurances diverses',
                'type_montant' => 'debit'
            ],
        ];
        
        foreach ($typesTransactions as $typeData) {
            $existingType = $manager->getRepository(TypeTransaction::class)
                ->findOneBy(['libelle' => $typeData['libelle']]);
                
            if (!$existingType) {
                $type = new TypeTransaction();
                $type->setLibelle($typeData['libelle']);
                $type->setDescription($typeData['description']);
                $type->setTypeMontantAutorise($typeData['type_montant']);
                
                $manager->persist($type);
            }
        }
        
        $manager->flush();
    }
}