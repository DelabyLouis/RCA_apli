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
        // Run on the VERY FIRST request, before anything else
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256], // Very high priority
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only run once per process
        if ($this->done) {
            return;
        }
        $this->done = true;

        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof PostgreSQLPlatform)) {
            return;
        }

        try {
            // CRITICAL: Disable constraints checking temporarily
            $this->connection->executeStatement('SET CONSTRAINTS ALL DEFERRED');
            error_log("[DropConstraintListener] Set constraints deferred");

            // Try to drop the constraint
            $this->connection->executeStatement(
                'ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "unique_numero_ordre_exercice"'
            );
            error_log("[DropConstraintListener] ✅ Dropped unique_numero_ordre_exercice");

            // Try alternate name
            $this->connection->executeStatement(
                'ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "unique_numero_ordem_exercice"'
            );
            error_log("[DropConstraintListener] ✅ Dropped alternate name");

            // CRITICAL: Commit the changes immediately
            $this->connection->commit();
            error_log("[DropConstraintListener] ✅ Committed constraint drop");

            // Re-enable constraints
            $this->connection->executeStatement('SET CONSTRAINTS ALL IMMEDIATE');
            error_log("[DropConstraintListener] ✅ Constraints re-enabled");

        } catch (\Exception $e) {
            error_log("[DropConstraintListener] ❌ Error: " . $e->getMessage());
            try {
                $this->connection->rollback();
            } catch (\Exception $rollbackEx) {
                error_log("[DropConstraintListener] Rollback error: " . $rollbackEx->getMessage());
            }
        }
    }
}
