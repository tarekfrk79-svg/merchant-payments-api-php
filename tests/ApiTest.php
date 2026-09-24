<?php
declare(strict_types=1);
namespace App\Tests;
use App\Entity\Merchant;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
final class ApiTest extends WebTestCase
{
    private KernelBrowser $client;
    protected function setUp(): void
    {
        $this->client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = [$em->getClassMetadata(Merchant::class), $em->getClassMetadata(Payment::class)];
        if ($em->getConnection()->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) {
            $tool = new SchemaTool($em);
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        } else {
            // CI runs migrations first against an isolated PostgreSQL test database.
            $em->getConnection()->executeStatement('TRUNCATE payments, merchants');
        }
    }
    private function request(string $method, string $uri, ?array $data = null, ?string $key = null, bool $auth = true): array
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($auth) { $headers['HTTP_X_API_KEY'] = 'test-api-key'; }
        if ($key !== null) { $headers['HTTP_IDEMPOTENCY_KEY'] = $key; }
        $this->client->request($method, $uri, [], [], $headers, $data === null ? null : json_encode($data, JSON_THROW_ON_ERROR));
        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
    private function merchant(): array { return $this->request('POST', '/api/merchants', ['name' => 'Demo Shop', 'email' => 'demo@example.test']); }
    private function paymentInput(): array { return ['merchantId' => $this->merchant()['id'], 'amount' => 1299, 'currency' => 'EUR', 'externalReference' => 'order_001']; }
    public function testCreateAndReadMerchant(): void
    {
        $merchant = $this->merchant();
        self::assertResponseStatusCodeSame(201);
        self::assertSame('Demo Shop', $merchant['name']);
        self::assertSame($merchant, $this->request('GET', '/api/merchants/'.$merchant['id']));
        self::assertResponseStatusCodeSame(200);
    }
    public function testCreateReadAndListPayment(): void
    {
        $input = $this->paymentInput();
        $payment = $this->request('POST', '/api/payments', $input, 'create-payment');
        self::assertResponseStatusCodeSame(201);
        self::assertSame(1299, $payment['amount']);
        self::assertSame('SUCCEEDED', $payment['status']);
        self::assertSame($payment, $this->request('GET', '/api/payments/'.$payment['id']));
        self::assertResponseStatusCodeSame(200);
        $list = $this->request('GET', '/api/merchants/'.$input['merchantId'].'/payments');
        self::assertSame([$payment], $list['data']);
    }
    public function testNegativeAmount(): void
    {
        $input = $this->paymentInput(); $input['amount'] = -1;
        $this->request('POST', '/api/payments', $input, 'negative');
        self::assertResponseStatusCodeSame(422);
    }
    public function testZeroAmount(): void
    {
        $input = $this->paymentInput(); $input['amount'] = 0;
        $this->request('POST', '/api/payments', $input, 'zero');
        self::assertResponseStatusCodeSame(422);
    }
    public function testFloatAndStringAmountsRejected(): void
    {
        $input = $this->paymentInput();
        foreach ([12.99, '1299', true, 2147483648] as $amount) {
            $input['amount'] = $amount;
            $this->request('POST', '/api/payments', $input, 'bad-amount');
            self::assertResponseStatusCodeSame(422);
        }
    }
    public function testMissingMerchant(): void
    {
        $this->request('POST', '/api/payments', ['merchantId' => '550e8400-e29b-41d4-a716-446655440000', 'amount' => 100, 'currency' => 'EUR', 'externalReference' => 'missing'], 'missing');
        self::assertResponseStatusCodeSame(404);
    }
    public function testIdenticalRetryReturnsOnePayment(): void
    {
        $input = $this->paymentInput();
        $first = $this->request('POST', '/api/payments', $input, 'retry');
        self::assertResponseStatusCodeSame(201);
        $second = $this->request('POST', '/api/payments', array_reverse($input, true), 'retry');
        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('Idempotency-Replayed', 'true');
        self::assertSame($first, $second);
        self::assertCount(1, $this->request('GET', '/api/merchants/'.$input['merchantId'].'/payments')['data']);
    }
    public function testConflictingRetry(): void
    {
        $input = $this->paymentInput();
        $this->request('POST', '/api/payments', $input, 'conflict');
        $input['amount']++;
        $this->request('POST', '/api/payments', $input, 'conflict');
        self::assertResponseStatusCodeSame(409);
        self::assertCount(1, $this->request('GET', '/api/merchants/'.$input['merchantId'].'/payments')['data']);
    }
    public function testMissingApiKey(): void
    {
        $this->request('POST', '/api/merchants', ['name' => 'Demo', 'email' => 'demo@example.test'], auth: false);
        self::assertResponseStatusCodeSame(401);
        $this->request('POST', '/api/payments', [], 'key', false);
        self::assertResponseStatusCodeSame(401);
    }
    public function testMissingIdempotencyKey(): void
    {
        $this->request('POST', '/api/payments', $this->paymentInput());
        self::assertResponseStatusCodeSame(400);
    }
    public function testInvalidJson(): void
    {
        $this->client->request('POST', '/api/merchants', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_API_KEY' => 'test-api-key'], '{broken');
        self::assertResponseStatusCodeSame(400);
        self::assertSame('Invalid JSON', json_decode((string) $this->client->getResponse()->getContent(), true)['error']['message']);
    }
    public function testValidationAndUnknownFields(): void
    {
        $this->request('POST', '/api/merchants', ['name' => '', 'email' => 'invalid']);
        self::assertResponseStatusCodeSame(422);
        $this->request('POST', '/api/merchants', ['name' => 'Demo', 'email' => 'demo@example.test', 'cardNumber' => 'not-accepted']);
        self::assertResponseStatusCodeSame(422);
    }
    public function testFailedSimulationIsReplayable(): void
    {
        $input = $this->paymentInput(); $input['externalReference'] = 'fail_demo';
        $first = $this->request('POST', '/api/payments', $input, 'failure');
        self::assertResponseStatusCodeSame(201);
        self::assertSame('FAILED', $first['status']);
        self::assertSame($first, $this->request('POST', '/api/payments', $input, 'failure'));
        self::assertResponseStatusCodeSame(200);
    }
    public function testIdempotencyScopedToMerchant(): void
    {
        $first = $this->request('POST', '/api/payments', $this->paymentInput(), 'shared');
        $second = $this->request('POST', '/api/payments', $this->paymentInput(), 'shared');
        self::assertResponseStatusCodeSame(201);
        self::assertNotSame($first['id'], $second['id']);
    }
    public function testMalformedAndUnknownIds(): void
    {
        foreach (['bad', '------------------------------------', '550e8400-e29b-41d4-a716-446655440000'] as $id) {
            $this->request('GET', '/api/payments/'.$id);
            self::assertResponseStatusCodeSame(404);
        }
    }
    public function testPaginationAndMethodErrors(): void
    {
        $merchant = $this->merchant();
        $this->request('GET', '/api/merchants/'.$merchant['id'].'/payments?limit=101');
        self::assertResponseStatusCodeSame(422);
        $this->request('GET', '/api/payments');
        self::assertResponseStatusCodeSame(405);
        self::assertResponseHeaderSame('Allow', 'POST');
    }
    public function testDatabaseRejectsDuplicateKey(): void
    {
        $input = $this->paymentInput();
        $this->request('POST', '/api/payments', $input, 'database-constraint');
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $merchant = $em->find(Merchant::class, $input['merchantId']);
        $em->persist(new Payment($merchant, 100, 'EUR', 'another', 'database-constraint', str_repeat('a', 64)));
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $em->flush();
    }
}
