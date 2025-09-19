<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241201000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create crypto_rates table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE crypto_rates (
            id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL,
            pair VARCHAR(10) NOT NULL,
            rate NUMERIC(20, 8) NOT NULL,
            timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_pair_timestamp (pair, timestamp),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE crypto_rates');
    }
}
