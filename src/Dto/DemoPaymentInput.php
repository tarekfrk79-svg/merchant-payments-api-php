<?php
declare(strict_types=1);
namespace App\Dto;
use App\Exception\ApiException;
final readonly class DemoPaymentInput
{
    public function __construct(public int $cents, public string $reference, public string $outcome, public string $key) {}
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        if (array_diff(array_keys($data), ['amount', 'reference', 'outcome', 'key']) !== []) { throw new ApiException(422, 'Champ de démonstration inconnu.'); }
        $amount = $data['amount'] ?? null;
        if (!is_string($amount) || !preg_match('/\A(0|[1-9][0-9]{0,2})(?:[.,]([0-9]{1,2}))?\z/D', $amount, $parts)) {
            throw new ApiException(422, 'Saisissez un montant en euros avec au maximum deux décimales.');
        }
        $cents = ((int) $parts[1]) * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
        if ($cents < 1 || $cents > 10000) { throw new ApiException(422, 'Le montant doit être compris entre 0,01 € et 100 €.'); }
        $reference = $data['reference'] ?? '';
        $outcome = $data['outcome'] ?? null;
        $key = $data['key'] ?? null;
        if (!is_string($reference) || ($reference !== '' && !preg_match('/\A[A-Za-z0-9_-]{1,48}\z/D', $reference))) { throw new ApiException(422, 'Référence fictive : 1 à 48 lettres, chiffres, tirets ou underscores.'); }
        if (!in_array($outcome, ['accepted', 'declined'], true)) { throw new ApiException(422, 'Choisissez paiement accepté ou refusé.'); }
        if (!is_string($key) || !preg_match('/\A[A-Za-z0-9_-]{16,64}\z/D', $key)) { throw new ApiException(422, 'Clé de démonstration invalide.'); }
        return new self($cents, $reference === '' ? 'commande_'.substr(hash('sha256', $key), 0, 12) : $reference, $outcome, $key);
    }
}
