<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove unique constraint on numero_ordre to allow duplicate order numbers
 */
final class Version20260723000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unique constraint on numero_ordre per exercice to allow duplicate order numbers';
    }

    public function up(Schema $schema): void
    {
        // NOOP: The unique constraint on numero_ordre + exercice was never created in production,
        // so there's nothing to drop. This migration exists as a marker for the codebase changes.
        // 
        // The Entity Transaction.php now allows duplicate numero_ordre values.
        // The form fields are configured to allow editing of numero_ordre.
    }

    public function down(Schema $schema): void
    {
        // NOOP: Rollback would require recreating the constraint, but it never existed in production
    }
}
