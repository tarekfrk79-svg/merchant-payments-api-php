<?php
declare(strict_types=1);
namespace App\Service;
use App\Dto\CreatePayment;
use App\Entity\Payment;
use App\Exception\ApiException;
use App\Repository\MerchantRepository;
use App\Repository\PaymentRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
final readonly class PaymentService
{
    public function __construct(private EntityManagerInterface $em, private ManagerRegistry $registry, private MerchantRepository $merchants, private PaymentRepository $payments, private PaymentProcessorInterface $processor) {}
    public function create(CreatePayment $input, string $key): PaymentResult
    {
        if (!preg_match('/\A[A-Za-z0-9._:-]{1,128}\z/D', $key)) { throw new ApiException(400, 'Idempotency-Key is required (1–128 ASCII letters, digits, ., _, :, -)'); }
        $merchant = $this->merchants->find($input->merchantId);
        if ($merchant === null) { throw new ApiException(404, 'Merchant not found'); }
        $hash = $input->fingerprint();
        $existing = $this->payments->byKey($merchant->getId(), $key);
        if ($existing !== null) { return $this->replay($existing, $hash); }
        $payment = new Payment($merchant, $input->amount, $input->currency, $input->externalReference, $key, $hash);
        // Pure simulation: no external side effect. A real gateway requires an outbox and its own idempotency contract.
        $payment->complete($this->processor->process($payment));
        try {
            $this->em->persist($payment);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            // flush rolls back and closes the manager; read the winner using a fresh manager.
            $manager = $this->registry->resetManager();
            $winner = $manager->getRepository(Payment::class)->findOneBy(['merchant' => $input->merchantId, 'idempotencyKey' => $key]);
            if (!$winner instanceof Payment) { throw $e; }
            return $this->replay($winner, $hash);
        }
        return new PaymentResult($payment, true);
    }
    private function replay(Payment $payment, string $hash): PaymentResult
    {
        if (!hash_equals($payment->getRequestHash(), $hash)) { throw new ApiException(409, 'Idempotency key already used with different content'); }
        return new PaymentResult($payment, false);
    }
}
