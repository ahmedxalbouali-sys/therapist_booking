<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201103339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE therapist DROP FOREIGN KEY FK_C3D632FA76ED395');
        $this->addSql('DROP INDEX UNIQ_C3D632FA76ED395 ON therapist');
        $this->addSql('ALTER TABLE therapist DROP user_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE therapist ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE therapist ADD CONSTRAINT FK_C3D632FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3D632FA76ED395 ON therapist (user_id)');
    }
}
