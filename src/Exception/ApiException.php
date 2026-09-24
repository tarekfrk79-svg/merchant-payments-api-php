<?php
declare(strict_types=1);
namespace App\Exception;
final class ApiException extends \RuntimeException
{
    /** @param array<string, list<string>> $details */
    public function __construct(public readonly int $status, string $message, public readonly array $details = []) { parent::__construct($message); }
}
