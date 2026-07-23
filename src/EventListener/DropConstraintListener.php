<?php

namespace App\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DropConstraintListener implements EventSubscriberInterface
{
    private bool $done = false;

    public function __construct(private Connection $connection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->done) {
            return;
        }
        $this->done = true;

        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof PostgreSQLPlatform)) {
            return;
        }

        try {
            error_log("[DropConstraintListener] 🚀 START - Finding and dropping numero* constraints");

            // Find the EXACT constraint name
            $constraints = $this->connection->executeQuery(
                "SELECT constraint_name FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' 
                 AND constraint_type = 'UNIQUE' 
                 AND table_schema = 'public'"
            )->fetchAllAssociative();

            error_log("[DropConstraintListener] Found " . count($constraints) . " UNIQUE constraints");

            $found = false;
            foreach ($constraints as $row) {
                $name = $row['constraint_name'];
                
                if (stripos($name, 'numero') !== false) {
                    error_log("[DropConstraintListener] 🎯 Dropping: $name");
                    $found = true;

                    $sql = 'ALTER TABLE "transaction" DROP CONSTRAINT "' . str_replace('"', '""', $name) . '"';
                    $this->connection->executeStatement($sql);
                    error_log("[DropConstraintListener] ✅ Dropped: $name");
                }
            }

            if (!$found) {
                error_log("[DropConstraintListener] ⚠️  No numero* UNIQUE constraint found");
            }

            // Verify
            $verify = $this->connection->executeQuery(
                "SELECT COUNT(*) as cnt FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' 
                 AND constraint_type = 'UNIQUE' 
                 AND table_schema = 'public'
                 AND constraint_name LIKE '%numero%'"
            )->fetchAssociative();

            $cnt = (int)($verify['cnt'] ?? 0);
            if ($cnt === 0) {
                error_log("[DropConstraintListener] ✅✅ SUCCESS: All numero constraints removed!");
            } else {
                error_log("[DropConstraintListener] ❌ FAILED: $cnt constraints still exist");
            }

        } catch (\Exception $e) {
            error_log("[DropConstraintListener] ❌ Exception: " . $e->getMessage());
        }
    }
}
