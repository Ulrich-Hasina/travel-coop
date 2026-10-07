<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007135654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE booking ADD departure_id INT NOT NULL');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE9395C3F3 FOREIGN KEY (customer_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE7704ED06 FOREIGN KEY (departure_id) REFERENCES departure (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_E00CEDDE9395C3F3 ON booking (customer_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDE7704ED06 ON booking (departure_id)');
        $this->addSql('ALTER TABLE departure ADD line_id INT NOT NULL');
        $this->addSql('ALTER TABLE departure ADD CONSTRAINT FK_45E9C6714D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_45E9C6714D7B7542 ON departure (line_id)');
        $this->addSql('ALTER TABLE "user" ADD email VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD phone VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD first_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD last_name VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE9395C3F3');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE7704ED06');
        $this->addSql('DROP INDEX IDX_E00CEDDE9395C3F3');
        $this->addSql('DROP INDEX IDX_E00CEDDE7704ED06');
        $this->addSql('ALTER TABLE booking DROP customer_id');
        $this->addSql('ALTER TABLE booking DROP departure_id');
        $this->addSql('ALTER TABLE departure DROP CONSTRAINT FK_45E9C6714D7B7542');
        $this->addSql('DROP INDEX IDX_45E9C6714D7B7542');
        $this->addSql('ALTER TABLE departure DROP line_id');
        $this->addSql('ALTER TABLE "user" DROP email');
        $this->addSql('ALTER TABLE "user" DROP phone');
        $this->addSql('ALTER TABLE "user" DROP first_name');
        $this->addSql('ALTER TABLE "user" DROP last_name');
    }
}
