<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add colors table and optional tag color relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE color (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hex_code VARCHAR(7) NOT NULL, UNIQUE INDEX uniq_color_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO color (name, hex_code) VALUES ('Rouge', '#D9534F'), ('Orange', '#F0AD4E'), ('Jaune', '#FFD166'), ('Vert', '#5CB85C'), ('Bleu', '#5BC0DE'), ('Violet', '#7E57C2'), ('Gris', '#6C757D'), ('Noir', '#212529')");
        $this->addSql('ALTER TABLE tag ADD color_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B7835C9961A5E FOREIGN KEY (color_id) REFERENCES color (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_389B7835C9961A5E ON tag (color_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B7835C9961A5E');
        $this->addSql('DROP INDEX IDX_389B7835C9961A5E ON tag');
        $this->addSql('ALTER TABLE tag DROP color_id');
        $this->addSql('DROP TABLE color');
    }
}
