<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216171410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_PHONE ON user');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d64935c246d5 TO UNIQ_IDENTIFIER_PASSWORD');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PHONE ON user (phone)');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_identifier_password TO UNIQ_8D93D64935C246D5');
    }
}
