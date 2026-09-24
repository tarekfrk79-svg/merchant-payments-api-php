<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260924000100 extends AbstractMigration
{
    public function getDescription(): string { return 'Merchants and simulated payments with database-enforced idempotency'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE merchants (id UUID NOT NULL, name VARCHAR(120) NOT NULL, email VARCHAR(254) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql("CREATE TABLE payments (id UUID NOT NULL, merchant_id UUID NOT NULL, amount INT NOT NULL CHECK (amount > 0), currency VARCHAR(3) NOT NULL CHECK (currency IN ('EUR', 'USD', 'GBP')), external_reference VARCHAR(120) NOT NULL, status VARCHAR(16) NOT NULL CHECK (status IN ('PENDING', 'SUCCEEDED', 'FAILED')), idempotency_key VARCHAR(128) NOT NULL, request_hash VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id), CONSTRAINT fk_payment_merchant FOREIGN KEY (merchant_id) REFERENCES merchants (id) ON DELETE RESTRICT)");
        $this->addSql('CREATE UNIQUE INDEX uniq_payment_merchant_idempotency ON payments (merchant_id, idempotency_key)');
        $this->addSql('CREATE INDEX idx_payment_merchant_created ON payments (merchant_id, created_at)');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE merchants');
    }
}
