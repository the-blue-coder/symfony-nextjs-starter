<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add locale column to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD locale VARCHAR(2) NOT NULL DEFAULT 'en'");
        $this->addSql('ALTER TABLE users ALTER COLUMN locale DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN locale');
    }
}
