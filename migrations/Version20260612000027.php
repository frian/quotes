<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612000027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add CSS class to colors';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE color ADD class VARCHAR(50) DEFAULT NULL');
        $this->addSql(
            "UPDATE color SET class = CASE name
                WHEN 'Jaune soleil' THEN 'color-sun-yellow'
                WHEN 'Turquoise' THEN 'color-turquoise'
                WHEN 'Orange vif' THEN 'color-bright-orange'
                WHEN 'Violet lilas' THEN 'color-lilac-purple'
                WHEN 'Rose framboise' THEN 'color-raspberry-pink'
                WHEN 'Vert émeraude' THEN 'color-emerald-green'
                WHEN 'Abricot' THEN 'color-apricot'
                WHEN 'Menthe franche' THEN 'color-fresh-mint'
                WHEN 'Citron' THEN 'color-lemon'
                WHEN 'Bleu vif' THEN 'color-bright-blue'
                ELSE class
            END"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE color DROP class');
    }
}
