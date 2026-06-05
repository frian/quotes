<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605171005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uniqueness constraints for music catalog entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_artist_name ON artist (name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_album_artist_title_year ON album (artist_id, title, release_year)');
        $this->addSql('CREATE UNIQUE INDEX uniq_song_album_title ON song (album_id, title)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_artist_name ON artist');
        $this->addSql('DROP INDEX uniq_album_artist_title_year ON album');
        $this->addSql('DROP INDEX uniq_song_album_title ON song');
    }
}
