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
            error_log("[DropConstraintListener] 🚀 START");

            // Use pg_constraint directly - more reliable than information_schema
            $result = $this->connection->executeQuery(
                "SELECT c.conname as constraint_name, 
                        (SELECT relname FROM pg_class WHERE oid = c.conrelid) as table_name,
                        CASE WHEN c.contype = 'u' THEN 'UNIQUE' 
                             WHEN c.contype = 'p' THEN 'PRIMARY KEY'
                             WHEN c.contype = 'f' THEN 'FOREIGN KEY'
                             ELSE c.contype END as constraint_type
                 FROM pg_constraint c
                 JOIN pg_class t ON c.conrelid = t.oid
                 JOIN pg_namespace n ON t.relnamespace = n.oid
                 WHERE t.relname = 'transaction' AND n.nspname = 'public'
                 ORDER BY c.conname"
            )->fetchAllAssociative();

            error_log("[DropConstraintListener] Found " . count($result) . " constraints on transaction table:");

            $found = false;
            foreach ($result as $row) {
                $name = $row['constraint_name'];
                $type = $row['constraint_type'];
                error_log("[DropConstraintListener]   - $name ($type)");
                
                if (stripos($name, 'numero') !== false) {
                    error_log("[DropConstraintListener] 🎯 TARGET FOUND: $name");
                    $found = true;

                    try {
                        $sql = 'ALTER TABLE "transaction" DROP CONSTRAINT "' . str_replace('"', '""', $name) . '"';
                        error_log("[DropConstraintListener] Executing: $sql");
                        $this->connection->executeStatement($sql);
                        error_log("[DropConstraintListener] ✅ Dropped: $name");
                    } catch (\Exception $e) {
                        error_log("[DropConstraintListener] ❌ Failed to drop $name: " . $e->getMessage());
                    }
                }
            }

            if (!$found) {
                error_log("[DropConstraintListener] ⚠️  No numero* constraint found");
            }

            // Final verification
            $verify = $this->connection->executeQuery(
                "SELECT c.conname FROM pg_constraint c
                 JOIN pg_class t ON c.conrelid = t.oid
                 JOIN pg_namespace n ON t.relnamespace = n.oid
                 WHERE t.relname = 'transaction' AND n.nspname = 'public'
                 AND c.conname LIKE '%numero%'"
            )->fetchAllAssociative();

            if (count($verify) === 0) {
                error_log("[DropConstraintListener] ✅ FINAL: All numero constraints successfully removed!");
            } else {
                error_log("[DropConstraintListener] ❌ FINAL: " . count($verify) . " numero constraint(s) still exist");
            }

        } catch (\Exception $e) {
            error_log("[DropConstraintListener] ❌ Exception: " . $e->getMessage());
        }
    }
}
