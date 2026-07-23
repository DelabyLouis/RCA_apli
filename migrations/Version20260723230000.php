<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * SIMPLEST POSSIBLE: Direct connection.executeStatement() with NO addSql()
 */
final class Version20260723230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unique_numero_ordre_exercice - DIRECT executeStatement';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof PostgreSQLPlatform)) {
            return;
        }

        try {
            // DIRECT: Don't use addSql, use executeStatement directly
            $this->connection->executeStatement('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "unique_numero_ordre_exercice"');
            error_log("[MIGRATION 230000] ✅ Dropped via executeStatement");
        } catch (\Exception $e) {
            error_log("[MIGRATION 230000] ⚠️  " . $e->getMessage());
        }

        // Try alternate name
        try {
            $this->connection->executeStatement('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "unique_numero_ordem_exercice"');
            error_log("[MIGRATION 230000] ✅ Dropped alternate name");
        } catch (\Exception $e) {
            error_log("[MIGRATION 230000] ⚠️  Alternate: " . $e->getMessage());
        }
    }

    public function down(Schema $schema): void {}
}
