<?php
declare(strict_types=1);
namespace App\Service;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
interface PaymentProcessorInterface { public function process(Payment $payment): PaymentStatus; }
