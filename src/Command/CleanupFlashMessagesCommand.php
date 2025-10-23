<?php
// Script utilitaire pour nettoyer les sessions et messages flash persistants
// Usage: php bin/console app:cleanup-flash-messages

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\FilesystemSessionHandler;

#[AsCommand(
    name: 'app:cleanup-flash-messages',
    description: 'Nettoie les messages flash persistants dans les sessions'
)]
class CleanupFlashMessagesCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sessionDir = sys_get_temp_dir() . '/symfony_sessions';
        
        if (!is_dir($sessionDir)) {
            $io->success('Aucun répertoire de session trouvé. Rien à nettoyer.');
            return Command::SUCCESS;
        }

        $files = glob($sessionDir . '/sess_*');
        $cleanedCount = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $content = file_get_contents($file);
                
                // Rechercher et supprimer les références aux messages flash
                $cleanContent = preg_replace('/\|_sf2_flashes\|[^|]*/', '', $content);
                
                if ($content !== $cleanContent) {
                    file_put_contents($file, $cleanContent);
                    $cleanedCount++;
                }
            }
        }

        $io->success(sprintf('Nettoyage terminé. %d session(s) modifiée(s).', $cleanedCount));

        return Command::SUCCESS;
    }
}