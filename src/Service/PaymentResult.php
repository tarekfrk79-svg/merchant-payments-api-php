<?php
declare(strict_types=1);
namespace App\Service;
use App\Entity\Payment;
final readonly class PaymentResult { public function __construct(public Payment $payment, public bool $created) {} }
