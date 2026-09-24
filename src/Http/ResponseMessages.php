<?php
declare(strict_types=1);
namespace App\Http;
final class ResponseMessages
{
    public static function get(int $status): string { return match ($status) { 404 => 'Resource not found', 405 => 'Method not allowed', default => 'Request rejected' }; }
}
