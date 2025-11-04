<?php

namespace App\Command;

use App\Service\AuditTrailService;
use App\Service\SoftDeleteService;
use App\Repository\PersonneRepository;
use App\Repository\AuditTrailRepository;
use App\Repository\ConsentementRgpdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rgpd-cleanup',
    description: 'Nettoie les données selon les durées de conservation RGPD',
)]
class RgpdCleanupCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditTrailService $auditService,
        private SoftDeleteService $softDeleteService,
        private PersonneRepository $personneRepository,
        private AuditTrailRepository $auditTrailRepository,
        private ConsentementRgpdRepository $consentementRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans modification réelle')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force l\'exécution sans confirmation')
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'Type d\'entité à nettoyer (audit, consentement, etc.)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $isDryRun = $input->getOption('dry-run');
        $isForce = $input->getOption('force');
        $entityType = $input->getOption('entity');

        $io->title('Nettoyage RGPD - Durées de conservation');

        if ($isDryRun) {
            $io->note('Mode SIMULATION activé - Aucune modification ne sera effectuée');
        }

        // 1. Nettoyer les logs d'audit (conservation 1 an)
        if (!$entityType || $entityType === 'audit') {
            $cleanedAudits = $this->cleanupAuditTrails($io, $isDryRun);
        }

        // 2. Nettoyer les anciens consentements (garder uniquement les plus récents)
        if (!$entityType || $entityType === 'consentement') {
            $cleanedConsents = $this->cleanupOldConsents($io, $isDryRun);
        }

        // 3. Supprimer définitivement les données en soft delete depuis plus de 3 ans
        if (!$entityType || $entityType === 'soft-delete') {
            $hardDeleted = $this->processScheduledHardDeletes($io, $isDryRun);
        }

        // 4. Identifier les personnes inactives (sans transactions depuis 3 ans)
        if (!$entityType || $entityType === 'inactive') {
            $inactivePersons = $this->identifyInactivePersons($io, $isDryRun);
        }

        $io->success('Nettoyage RGPD terminé');
        
        return Command::SUCCESS;
    }

    private function cleanupAuditTrails(SymfonyStyle $io, bool $isDryRun): int
    {
        $io->section('Nettoyage des logs d\'audit');
        
        // Conservation 1 an pour les logs d'audit
        $retentionDays = 365;
        $cutoffDate = new \DateTime("-{$retentionDays} days");
        
        if ($isDryRun) {
            // Compter les enregistrements à supprimer
            $count = $this->auditTrailRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->andWhere('a.created_at < :date')
                ->setParameter('date', $cutoffDate)
                ->getQuery()
                ->getSingleScalarResult();
            
            $io->info("Simulation: {$count} logs d'audit seraient supprimés (antérieurs au {$cutoffDate->format('Y-m-d')})");
            return $count;
        }
        
        $deleted = $this->auditService->cleanupOldAudits($retentionDays);
        $io->success("{$deleted} logs d'audit supprimés");
        
        return $deleted;
    }

    private function cleanupOldConsents(SymfonyStyle $io, bool $isDryRun): int
    {
        $io->section('Nettoyage des anciens consentements');
        
        // Garder seulement le consentement le plus récent par utilisateur et par type
        $qb = $this->entityManager->createQueryBuilder()
            ->select('c1')
            ->from('App\Entity\ConsentementRgpd', 'c1')
            ->where('EXISTS(
                SELECT 1 FROM App\Entity\ConsentementRgpd c2 
                WHERE c2.user = c1.user 
                AND c2.type_consentement = c1.type_consentement 
                AND c2.date_consentement > c1.date_consentement
            )');
        
        $oldConsents = $qb->getQuery()->getResult();
        
        if ($isDryRun) {
            $io->info("Simulation: " . count($oldConsents) . " anciens consentements seraient supprimés");
            return count($oldConsents);
        }
        
        foreach ($oldConsents as $consent) {
            $this->entityManager->remove($consent);
        }
        
        $this->entityManager->flush();
        
        $count = count($oldConsents);
        $io->success("{$count} anciens consentements supprimés");
        
        return $count;
    }

    private function processScheduledHardDeletes(SymfonyStyle $io, bool $isDryRun): int
    {
        $io->section('Suppression définitive programmée');
        
        // Récupérer les suppressions programmées dont la date est dépassée
        $scheduledDeletes = $this->softDeleteService->scheduleAutomaticCleanup();
        
        if ($isDryRun) {
            $io->info("Simulation: " . count($scheduledDeletes) . " entités seraient supprimées définitivement");
            
            foreach ($scheduledDeletes as $audit) {
                $io->text("- {$audit->getEntityType()} ID {$audit->getEntityId()} (prévu le {$audit->getScheduledHardDelete()->format('Y-m-d')})");
            }
            
            return count($scheduledDeletes);
        }

        $count = 0;
        foreach ($scheduledDeletes as $audit) {
            // La suppression a déjà été faite, marquer comme traitée
            $audit->setScheduledHardDelete(null);
            $this->entityManager->persist($audit);
            $count++;
        }
        
        $this->entityManager->flush();
        
        $io->success("{$count} suppressions définitives traitées");
        
        return $count;
    }

    private function identifyInactivePersons(SymfonyStyle $io, bool $isDryRun): int
    {
        $io->section('Identification des personnes inactives');
        
        // Trouver les personnes sans transaction depuis 3 ans
        $cutoffDate = new \DateTime('-3 years');
        
        $qb = $this->personneRepository->createQueryBuilder('p')
            ->leftJoin('p.transactions', 't')
            ->groupBy('p.id_personne')
            ->having('MAX(t.date_transaction) < :cutoff OR MAX(t.date_transaction) IS NULL')
            ->andWhere('p.deleted_at IS NULL') // Exclure les déjà supprimées
            ->setParameter('cutoff', $cutoffDate);
            
        $inactivePersons = $qb->getQuery()->getResult();
        
        if ($isDryRun) {
            $io->info("Simulation: " . count($inactivePersons) . " personnes inactives identifiées");
            
            foreach (array_slice($inactivePersons, 0, 5) as $person) {
                $io->text("- {$person->getPrenom()} {$person->getNom()} ({$person->getEmail()})");
            }
            
            if (count($inactivePersons) > 5) {
                $io->text("... et " . (count($inactivePersons) - 5) . " autres");
            }
            
            return count($inactivePersons);
        }

        // En production, on pourrait notifier ces personnes avant suppression
        $io->warning("Personnes inactives identifiées (action manuelle requise): " . count($inactivePersons));
        
        return count($inactivePersons);
    }
}