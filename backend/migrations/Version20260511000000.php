<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate from JWT/password auth to Clerk';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_tokens DROP CONSTRAINT FK_3967A216A76ED395');
        $this->addSql('DROP TABLE IF EXISTS refresh_tokens');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS password');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS google_id');
        $this->addSql('ALTER TABLE users ADD clerk_user_id VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE users ALTER COLUMN clerk_user_id DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9A98FCC3F ON users (clerk_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E9A98FCC3F');
        $this->addSql('ALTER TABLE users DROP COLUMN clerk_user_id');
        $this->addSql('ALTER TABLE users ADD password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD google_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE password_reset_tokens (id UUID NOT NULL, token VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3967A2165F37A13B ON password_reset_tokens (token)');
        $this->addSql('CREATE INDEX IDX_3967A216A76ED395 ON password_reset_tokens (user_id)');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
    }
}
