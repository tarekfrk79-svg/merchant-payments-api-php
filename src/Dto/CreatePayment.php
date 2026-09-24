<?php
declare(strict_types=1);
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;
final readonly class CreatePayment
{
    public function __construct(
        #[Assert\NotBlank, Assert\Uuid] public string $merchantId,
        #[Assert\Positive, Assert\LessThanOrEqual(2147483647)] public int $amount,
        #[Assert\Choice(choices: ['EUR', 'USD', 'GBP'])] public string $currency,
        #[Assert\NotBlank, Assert\Length(max: 120)] public string $externalReference,
    ) {}
    public function fingerprint(): string
    {
        // Canonical field order: whitespace and JSON key order do not change identity.
        return hash('sha256', json_encode([$this->merchantId, $this->amount, $this->currency, $this->externalReference], JSON_THROW_ON_ERROR));
    }
}
