<?php
declare(strict_types=1);
namespace App\Service;
use App\Dto\CreatePayment;
use App\Dto\DemoPaymentInput;
use App\Entity\Merchant;
use App\Entity\Payment;
use App\Exception\ApiException;
use App\Repository\MerchantRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
final readonly class DemoService
{
    public const MAX_PAYMENTS = 10;
    public function __construct(private EntityManagerInterface $em, private MerchantRepository $merchants, private PaymentRepository $payments, private PaymentService $service) {}
    public function token(SessionInterface $session): string
    {
        $token = $session->get('demo_token');
        $started = $session->get('demo_started');
        if (!is_string($token) || !is_int($started) || time() - $started >= 3600) {
            $session->clear();
            $session->migrate(true);
            $token = bin2hex(random_bytes(32));
            $session->set('demo_token', $token);
            $session->set('demo_started', time());
        }
        return $token;
    }
    public function authorize(SessionInterface $session, string $token): void
    {
        $expected = $session->get('demo_token');
        $started = $session->get('demo_started');
        if (!is_string($expected) || !is_int($started) || time() - $started >= 3600 || !hash_equals($expected, $token)) { throw new ApiException(403, 'Session expirée ou requête invalide. Rechargez la démonstration.'); }
        // Native session locking serializes requests for this visitor, including quota checks.
        $window = $session->get('demo_window', 0);
        $count = $session->get('demo_attempts', 0);
        if (!is_int($window) || time() - $window >= 60) { $window = time(); $count = 0; }
        if (!is_int($count)) { $count = 0; }
        if ($count >= 30) { throw new ApiException(429, 'Trop de tentatives. Réessayez dans une minute.'); }
        $session->set('demo_window', $window);
        $session->set('demo_attempts', $count + 1);
    }
    public function create(SessionInterface $session, DemoPaymentInput $input): PaymentResult
    {
        $merchant = $this->merchant($session);
        if ($merchant === null) {
            $merchant = new Merchant('Interactive demo', 'interactive-demo@example.test', true);
            $this->em->persist($merchant);
            $this->em->flush();
            $session->set('demo_merchant', $merchant->getId());
        }
        $key = 'demo_'.$input->key;
        if ($this->payments->byKey($merchant->getId(), $key) === null && $this->payments->count(['merchant' => $merchant]) >= self::MAX_PAYMENTS) { throw new ApiException(429, 'Limite atteinte : 10 paiements par session. Vous pouvez encore réessayer un paiement existant.'); }
        $reference = ($input->outcome === 'declined' ? 'fail_demo_' : 'demo_').$input->reference;
        return $this->service->create(new CreatePayment($merchant->getId(), $input->cents, 'EUR', $reference), $key);
    }
    /** @return list<array{id: string, merchantId: string, amount: int, currency: string, externalReference: string, status: string, createdAt: string}> */
    public function history(SessionInterface $session): array
    {
        $merchant = $this->merchant($session);
        if ($merchant === null) { return []; }
        return array_map(static fn (Payment $p): array => $p->toArray(), $this->payments->findBy(['merchant' => $merchant], ['createdAt' => 'DESC', 'id' => 'DESC'], self::MAX_PAYMENTS));
    }
    private function merchant(SessionInterface $session): ?Merchant
    {
        $id = $session->get('demo_merchant');
        $merchant = is_string($id) ? $this->merchants->find($id) : null;
        return $merchant !== null && $merchant->isDemo() ? $merchant : null;
    }
}
