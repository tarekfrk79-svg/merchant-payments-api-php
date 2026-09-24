<?php
declare(strict_types=1);
namespace App\Tests;
use App\Command\CleanupDemoCommand;
use App\Entity\Merchant;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
final class DemoTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $token;
    protected function setUp(): void
    {
        $this->client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        if ($em->getConnection()->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) {
            $tool = new SchemaTool($em);
            $metadata = [$em->getClassMetadata(Merchant::class), $em->getClassMetadata(Payment::class)];
            $tool->dropSchema($metadata); $tool->createSchema($metadata);
        } else { $em->getConnection()->executeStatement('TRUNCATE payments, merchants'); }
        $this->token = (string) $this->client->request('GET', '/demo')->filter('meta[name="demo-token"]')->attr('content');
    }
    private function payload(): array { return ['amount' => '12,99', 'reference' => 'commande_001', 'outcome' => 'accepted', 'key' => 'demo-test-key-0001']; }
    private function post(array $data, ?string $token = null): array
    {
        $this->client->request('POST', '/demo/payments', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_DEMO_TOKEN' => $token ?? $this->token], json_encode($data, JSON_THROW_ON_ERROR));
        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
    public function testPageAndJavascriptContainNoApiKey(): void
    {
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('h1', 'Un paiement.');
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('test-api-key', $html);
        self::assertStringNotContainsString('DEMO_API_KEY', $html);
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        $this->client->request('GET', '/demo/app.js');
        self::assertResponseStatusCodeSame(200);
        $js = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('test-api-key', $js);
        self::assertStringNotContainsString('DEMO_API_KEY', $js);
        self::assertStringNotContainsString('X-API-Key', $js);
    }
    public function testCreateAndReplaySameUuid(): void
    {
        $first = $this->post($this->payload());
        self::assertResponseStatusCodeSame(201);
        self::assertSame(1299, $first['payment']['amount']);
        self::assertSame('SUCCEEDED', $first['payment']['status']);
        self::assertFalse($first['replayed']);
        $second = $this->post($this->payload());
        self::assertResponseStatusCodeSame(200);
        self::assertTrue($second['replayed']);
        self::assertSame($first['payment']['id'], $second['payment']['id']);
        self::assertCount(1, $second['history']);
        self::assertStringNotContainsString('test-api-key', (string) $this->client->getResponse()->getContent());
    }
    public function testDeclinedAndGeneratedReference(): void
    {
        $input = $this->payload(); $input['outcome'] = 'declined'; $input['reference'] = '';
        $first = $this->post($input);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('FAILED', $first['payment']['status']);
        self::assertStringStartsWith('fail_demo_commande_', $first['payment']['externalReference']);
        self::assertSame($first['payment']['id'], $this->post($input)['payment']['id']);
        self::assertResponseStatusCodeSame(200);
    }
    public function testInvalidAmountsAndMaximum(): void
    {
        foreach (['0', '-1', '100.01', '101', '1.001', '1e2', '', 'NaN', '1000', 1.5, 10, true] as $amount) {
            $this->post([...$this->payload(), 'amount' => $amount]);
            self::assertResponseStatusCodeSame(422);
        }
        $result = $this->post([...$this->payload(), 'amount' => '100.00']);
        self::assertResponseStatusCodeSame(201);
        self::assertSame(10000, $result['payment']['amount']);
    }
    public function testStrictFieldsAndConflict(): void
    {
        foreach ([['reference' => '<script>'], ['reference' => 'email@example.com'], ['outcome' => 'invalid'], ['key' => 'x'], ['merchantId' => 'not-allowed']] as $change) {
            $this->post([...$this->payload(), ...$change]);
            self::assertResponseStatusCodeSame(422);
        }
        $this->post($this->payload());
        $this->post([...$this->payload(), 'amount' => '15']);
        self::assertResponseStatusCodeSame(409);
    }
    public function testSessionQuotaStillAllowsReplay(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $result = $this->post([...$this->payload(), 'key' => 'demo-test-key-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT)]);
            self::assertResponseStatusCodeSame(201);
        }
        self::assertCount(10, $result['history']);
        $this->post([...$this->payload(), 'key' => 'demo-extra-key-00001']);
        self::assertResponseStatusCodeSame(429);
        $this->post($this->payload());
        self::assertResponseStatusCodeSame(200);
    }
    public function testCsrfAndApiProtection(): void
    {
        $this->post($this->payload(), 'wrong-token');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/api/payments', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        self::assertResponseStatusCodeSame(401);
    }
    public function testSessionsAreIsolated(): void
    {
        $first = $this->post($this->payload());
        $oldToken = $this->token;
        $this->client->getCookieJar()->clear();
        $this->token = (string) $this->client->request('GET', '/demo')->filter('meta[name="demo-token"]')->attr('content');
        self::assertNotSame($oldToken, $this->token);
        $this->client->request('GET', '/demo/history', [], [], ['HTTP_X_DEMO_TOKEN' => $this->token]);
        self::assertSame([], json_decode((string) $this->client->getResponse()->getContent(), true)['history']);
        $this->post($this->payload(), $oldToken);
        self::assertResponseStatusCodeSame(403);
        $second = $this->post($this->payload());
        self::assertResponseStatusCodeSame(201);
        self::assertNotSame($first['payment']['id'], $second['payment']['id']);
        self::assertCount(1, $second['history']);
    }
    public function testRateLimit(): void
    {
        for ($i = 0; $i < 30; ++$i) {
            $this->client->request('GET', '/demo/history', [], [], ['HTTP_X_DEMO_TOKEN' => $this->token]);
            self::assertResponseStatusCodeSame(200);
        }
        $this->client->request('GET', '/demo/history', [], [], ['HTTP_X_DEMO_TOKEN' => $this->token]);
        self::assertResponseStatusCodeSame(429);
    }
    public function testCleanupOnlyDeletesExpiredDemoRecords(): void
    {
        $old = $this->post($this->payload());
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $regular = new Merchant('API merchant', 'regular@example.test');
        $recent = new Merchant('Interactive demo', 'interactive-demo@example.test', true);
        $em->persist($regular); $em->persist($recent); $em->flush();
        $em->getConnection()->executeStatement('UPDATE merchants SET created_at = ? WHERE id IN (?, ?)', ['2020-01-01 00:00:00', $old['payment']['merchantId'], $regular->getId()]);
        $tester = new CommandTester(self::getContainer()->get(CleanupDemoCommand::class));
        self::assertSame(0, $tester->execute([]));
        self::assertSame(3, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM merchants'));
        self::assertSame(0, $tester->execute(['--execute' => true]));
        self::assertSame(2, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM merchants'));
        self::assertSame(0, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM payments'));
        self::assertSame(1, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM merchants WHERE id = ?', [$regular->getId()]));
    }
}
