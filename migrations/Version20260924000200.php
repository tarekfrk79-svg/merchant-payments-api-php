<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260924000200 extends AbstractMigration
{
    public function getDescription(): string { return 'Explicitly mark interactive demo merchants for safe cleanup'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE merchants ADD is_demo BOOLEAN DEFAULT FALSE NOT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE merchants DROP COLUMN is_demo'); }
}
