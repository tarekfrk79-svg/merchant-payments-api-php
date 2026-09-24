<?php
declare(strict_types=1);
namespace App\Service;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
final class FakePaymentProcessor implements PaymentProcessorInterface
{
    public function process(Payment $payment): PaymentStatus
    {
        return str_starts_with($payment->getExternalReference(), 'fail_') ? PaymentStatus::FAILED : PaymentStatus::SUCCEEDED;
    }
}
