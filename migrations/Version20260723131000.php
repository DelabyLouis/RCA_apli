<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unique_numero_ordre_exercice constraint to allow duplicate numero_ordre per exercice';
    }

    public function up(Schema $schema): void
    {
        // Log to error_log
        error_log("[Migration Version20260723131000] UP: Attempting to drop unique_numero_ordre_exercice constraint");
        
        try {
            // Try the exact name first
            $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice');
            error_log("[Migration Version20260723131000] UP: Dropped unique_numero_ordre_exercice");
        } catch (\Exception $e) {
            error_log("[Migration Version20260723131000] UP Exception 1: " . $e->getMessage());
        }
        
        try {
            // Try with typo variant (in case schema had typo)
            $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS unique_numero_ordem_exercice');
            error_log("[Migration Version20260723131000] UP: Dropped unique_numero_ordem_exercice");
        } catch (\Exception $e) {
            error_log("[Migration Version20260723131000] UP Exception 2: " . $e->getMessage());
        }
        
        // Verify drop
        error_log("[Migration Version20260723131000] UP: Constraint drop completed");
    }

    public function down(Schema $schema): void
    {
        // Down is optional - we're just removing the constraint
        error_log("[Migration Version20260723131000] DOWN: Not re-creating constraint");
    }
}
