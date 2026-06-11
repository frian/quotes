<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611234930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Load tag color palette';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "INSERT INTO color (name, hex_code) VALUES
                ('Jaune soleil', '#FFD166'),
                ('Turquoise', '#6FDDDB'),
                ('Orange vif', '#FFAB40'),
                ('Violet lilas', '#C9B1FF'),
                ('Rose framboise', '#FFA8C5'),
                ('Vert émeraude', '#A8E6CF'),
                ('Abricot', '#FFD6A5'),
                ('Menthe franche', '#B5EAD7'),
                ('Citron', '#FFFB87'),
                ('Bleu vif', '#96D4FF')
            ON DUPLICATE KEY UPDATE hex_code = VALUES(hex_code)"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE FROM color WHERE name IN (
                'Jaune soleil',
                'Turquoise',
                'Orange vif',
                'Violet lilas',
                'Rose framboise',
                'Vert émeraude',
                'Abricot',
                'Menthe franche',
                'Citron',
                'Bleu vif'
            )"
        );
    }
}
