<?php
declare(strict_types=1);
namespace App\Tests;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
final class HealthTest extends WebTestCase
{
    public function testHealth(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health');
        self::assertResponseIsSuccessful();
        self::assertSame('ok', json_decode((string) $client->getResponse()->getContent(), true)['status']);
    }
}
