<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605161510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the initial music notebook tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE artist (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE album (id INT AUTO_INCREMENT NOT NULL, artist_id INT NOT NULL, title VARCHAR(255) NOT NULL, release_year INT NOT NULL, INDEX IDX_39986E43B7970CF8 (artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song (id INT AUTO_INCREMENT NOT NULL, album_id INT NOT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_33EDEEA1B7970CF8 (album_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_excerpt (id INT AUTO_INCREMENT NOT NULL, song_id INT NOT NULL, body LONGTEXT NOT NULL, position INT DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_77D03C0EA0BDB2F3 (song_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_excerpt_tag (song_excerpt_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_23F152C14E83442 (song_excerpt_id), INDEX IDX_23F152CBAD26311 (tag_id), PRIMARY KEY(song_excerpt_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX uniq_tag_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA1B7970CF8 FOREIGN KEY (album_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE song_excerpt ADD CONSTRAINT FK_77D03C0EA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_excerpt_tag ADD CONSTRAINT FK_23F152C14E83442 FOREIGN KEY (song_excerpt_id) REFERENCES song_excerpt (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_excerpt_tag ADD CONSTRAINT FK_23F152CBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album DROP CONSTRAINT FK_39986E43B7970CF8');
        $this->addSql('ALTER TABLE song DROP CONSTRAINT FK_33EDEEA1B7970CF8');
        $this->addSql('ALTER TABLE song_excerpt DROP CONSTRAINT FK_77D03C0EA0BDB2F3');
        $this->addSql('ALTER TABLE song_excerpt_tag DROP CONSTRAINT FK_23F152C14E83442');
        $this->addSql('ALTER TABLE song_excerpt_tag DROP CONSTRAINT FK_23F152CBAD26311');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE song');
        $this->addSql('DROP TABLE song_excerpt');
        $this->addSql('DROP TABLE song_excerpt_tag');
        $this->addSql('DROP TABLE tag');
    }
}
