<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605213549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional source URL to songs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song ADD source_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song DROP source_url');
    }
}
