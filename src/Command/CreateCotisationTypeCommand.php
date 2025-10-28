<?php

namespace App\Command;

use App\Entity\TypeTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-cotisation-type',
    description: 'Crée le type de transaction "cotisation" pour les attestations fiscales',
)]
class CreateCotisationTypeCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si le type "cotisation" existe déjà
        $cotisationType = $this->entityManager->getRepository(TypeTransaction::class)
            ->findOneBy(['libelle' => 'cotisation']);
            
        if ($cotisationType) {
            $io->warning('Le type de transaction "cotisation" existe déjà.');
            return Command::SUCCESS;
        }

        // Créer le type "cotisation"
        $cotisationType = new TypeTransaction();
        $cotisationType->setLibelle('cotisation');
        $cotisationType->setDescription('Cotisations des membres de l\'association (ouvre droit aux attestations fiscales)');
        $cotisationType->setTypeMontantAutorise('credit'); // Seuls les montants positifs (recettes)
        
        $this->entityManager->persist($cotisationType);
        $this->entityManager->flush();

        $io->success('Le type de transaction "cotisation" a été créé avec succès !');
        $io->note('Vous pouvez maintenant créer des transactions de type "cotisation" et générer des attestations fiscales.');

        return Command::SUCCESS;
    }
}