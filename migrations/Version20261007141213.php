<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007141213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure ADD seats INT NOT NULL');
        $this->addSql('ALTER TABLE departure ALTER price TYPE NUMERIC(10, 2)');
        $this->addSql('ALTER TABLE line ALTER normal_duration TYPE INT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure DROP seats');
        $this->addSql('ALTER TABLE departure ALTER price TYPE INT');
        $this->addSql('ALTER TABLE line ALTER normal_duration TYPE NUMERIC(5, 2)');
    }
}
