<?php
declare(strict_types=1);
namespace App\Http;
use App\Exception\ApiException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
final readonly class ApiKeySubscriber implements EventSubscriberInterface
{
    public function __construct(#[Autowire('%env(DEMO_API_KEY)%')] private string $apiKey) {}
    public static function getSubscribedEvents(): array { return [KernelEvents::REQUEST => ['authenticate', 8]]; }
    public function authenticate(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), '/api/') || in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) { return; }
        if ($this->apiKey === '' || !hash_equals($this->apiKey, $request->headers->get('X-API-Key', ''))) { throw new ApiException(401, 'Invalid or missing API key'); }
    }
}
