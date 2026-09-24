<?php
declare(strict_types=1);
namespace App\Entity;
use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
#[ORM\UniqueConstraint(name: 'uniq_payment_merchant_idempotency', columns: ['merchant_id', 'idempotency_key'])]
#[ORM\Index(name: 'idx_payment_merchant_created', columns: ['merchant_id', 'created_at'])]
class Payment
{
    #[ORM\Id, ORM\Column(type: 'guid')]
    private string $id;
    #[ORM\ManyToOne(targetEntity: Merchant::class), ORM\JoinColumn(name: 'merchant_id', nullable: false, onDelete: 'RESTRICT')]
    private Merchant $merchant;
    #[ORM\Column(type: 'integer')]
    private int $amount;
    #[ORM\Column(length: 3)]
    private string $currency;
    #[ORM\Column(name: 'external_reference', length: 120)]
    private string $externalReference;
    #[ORM\Column(length: 16, enumType: PaymentStatus::class)]
    private PaymentStatus $status = PaymentStatus::PENDING;
    #[ORM\Column(name: 'idempotency_key', length: 128)]
    private string $idempotencyKey;
    #[ORM\Column(name: 'request_hash', length: 64)]
    private string $requestHash;
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    public function __construct(Merchant $merchant, int $amount, string $currency, string $externalReference, string $idempotencyKey, string $requestHash)
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->merchant = $merchant;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->externalReference = $externalReference;
        $this->idempotencyKey = $idempotencyKey;
        $this->requestHash = $requestHash;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
    public function getIdempotencyKey(): string { return $this->idempotencyKey; }
    public function getRequestHash(): string { return $this->requestHash; }
    public function getExternalReference(): string { return $this->externalReference; }
    public function complete(PaymentStatus $status): void { $this->status = $status; }
    /** @return array{id: string, merchantId: string, amount: int, currency: string, externalReference: string, status: string, createdAt: string} */
    public function toArray(): array
    {
        return ['id' => $this->id, 'merchantId' => $this->merchant->getId(), 'amount' => $this->amount, 'currency' => $this->currency, 'externalReference' => $this->externalReference, 'status' => $this->status->value, 'createdAt' => $this->createdAt->format(DATE_ATOM)];
    }
}
