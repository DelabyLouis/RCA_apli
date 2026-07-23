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
            error_log("[DropConstraintListener] Not PostgreSQL, skipping");
            return;
        }

        try {
            error_log("[DropConstraintListener] ========== START ==========");
            
            // Get database and schema info
            $currentDb = $this->connection->executeQuery("SELECT current_database()")->fetchOne();
            $currentSchema = $this->connection->executeQuery("SELECT current_schema()")->fetchOne();
            
            error_log("[DropConstraintListener] Database: $currentDb");
            error_log("[DropConstraintListener] Schema: $currentSchema");
            
            // === Direct approach: Try dropping by exact name immediately ===
            error_log("[DropConstraintListener] === DIRECT DROP ATTEMPT ===");
            
            $dropNames = [
                'unique_numero_ordem_exercice',
                'unique_numero_ordre_exercice',
                'uq_numero_ordem_exercice',
                'uq_numero_ordre_exercice',
            ];
            
            foreach ($dropNames as $name) {
                try {
                    error_log("[DropConstraintListener] Trying: ALTER TABLE \"transaction\" DROP CONSTRAINT \"$name\"");
                    $this->connection->executeStatement(
                        "ALTER TABLE \"transaction\" DROP CONSTRAINT \"$name\""
                    );
                    error_log("[DropConstraintListener] ✅ Successfully dropped: $name");
                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    if (stripos($msg, 'does not exist') !== false || 
                        stripos($msg, 'not found') !== false) {
                        error_log("[DropConstraintListener] ⚠️  $name doesn't exist: " . substr($msg, 0, 100));
                    } else {
                        error_log("[DropConstraintListener] ❌ Drop $name failed: " . substr($msg, 0, 100));
                    }
                }
            }
            
            // === Diagnostic: Query all constraints ===
            error_log("[DropConstraintListener] === DIAGNOSTIC QUERIES ===");
            
            error_log("[DropConstraintListener] Approach 1: information_schema.table_constraints");
            try {
                $result = $this->connection->executeQuery(
                    "SELECT constraint_name, constraint_type FROM information_schema.table_constraints 
                     WHERE table_schema = current_schema() AND table_name = 'transaction'
                     ORDER BY constraint_name"
                )->fetchAllAssociative();
                
                error_log("[DropConstraintListener] Found: " . count($result) . " constraints");
                foreach ($result as $row) {
                    error_log("[DropConstraintListener]   - {$row['constraint_name']} ({$row['constraint_type']})");
                }
            } catch (\Exception $e) {
                error_log("[DropConstraintListener] Query 1 failed: " . substr($e->getMessage(), 0, 100));
            }
            
            error_log("[DropConstraintListener] Approach 2: pg_constraint");
            try {
                $result = $this->connection->executeQuery(
                    "SELECT c.conname, 
                            CASE WHEN c.contype = 'u' THEN 'UNIQUE' 
                                 WHEN c.contype = 'p' THEN 'PRIMARY KEY'
                                 WHEN c.contype = 'f' THEN 'FOREIGN KEY'
                                 WHEN c.contype = 'c' THEN 'CHECK'
                                 ELSE c.contype END as type
                     FROM pg_constraint c
                     JOIN pg_class t ON c.conrelid = t.oid
                     JOIN pg_namespace n ON t.relnamespace = n.oid
                     WHERE t.relname = 'transaction'
                     ORDER BY c.conname"
                )->fetchAllAssociative();
                
                error_log("[DropConstraintListener] Found: " . count($result) . " constraints");
                foreach ($result as $row) {
                    error_log("[DropConstraintListener]   - {$row['conname']} ({$row['type']})");
                }
            } catch (\Exception $e) {
                error_log("[DropConstraintListener] Query 2 failed: " . substr($e->getMessage(), 0, 100));
            }
            
            error_log("[DropConstraintListener] Approach 3: information_schema.key_column_usage (contains numero)");
            try {
                $result = $this->connection->executeQuery(
                    "SELECT constraint_name, column_name FROM information_schema.key_column_usage 
                     WHERE table_schema = current_schema() AND table_name = 'transaction'
                     AND (constraint_name LIKE '%numero%' OR column_name LIKE '%numero%')"
                )->fetchAllAssociative();
                
                error_log("[DropConstraintListener] Found: " . count($result) . " numero-related constraints");
                foreach ($result as $row) {
                    error_log("[DropConstraintListener]   - {$row['constraint_name']}: {$row['column_name']}");
                }
            } catch (\Exception $e) {
                error_log("[DropConstraintListener] Query 3 failed: " . substr($e->getMessage(), 0, 100));
            }
            
            // === Final verification ===
            error_log("[DropConstraintListener] === FINAL CHECK ===");
            try {
                $result = $this->connection->executeQuery(
                    "SELECT c.conname FROM pg_constraint c
                     JOIN pg_class t ON c.conrelid = t.oid
                     WHERE t.relname = 'transaction'
                     AND (c.conname LIKE '%numero%' OR c.conname LIKE '%ordem%')"
                )->fetchAllAssociative();
                
                if (count($result) === 0) {
                    error_log("[DropConstraintListener] ✅ All numero constraints successfully removed!");
                } else {
                    error_log("[DropConstraintListener] ❌ Still have " . count($result) . " numero constraints:");
                    foreach ($result as $row) {
                        error_log("[DropConstraintListener]   - REMAINING: {$row['conname']}");
                    }
                }
            } catch (\Exception $e) {
                error_log("[DropConstraintListener] Final check failed: " . substr($e->getMessage(), 0, 100));
            }
            
            error_log("[DropConstraintListener] ========== END ==========");
            
        } catch (\Exception $e) {
            error_log("[DropConstraintListener] ❌ FATAL: " . $e->getMessage());
            error_log("[DropConstraintListener] Trace: " . $e->getTraceAsString());
        }
    }
}
