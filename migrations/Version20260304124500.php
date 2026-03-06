<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop price column from unit (price is calculable)';
    }

    public function up(Schema $schema): void
    {
        // Drop the price column from the unit table
        $this->addSql('ALTER TABLE unit DROP COLUMN price');
    }

    public function down(Schema $schema): void
    {
        // Recreate the price column if the migration is rolled back
        $this->addSql('ALTER TABLE unit ADD price NUMERIC(10, 2) DEFAULT NULL');
    }
}
