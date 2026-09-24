<?php
declare(strict_types=1);
namespace App\Http;
use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
final class ErrorSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array { return [KernelEvents::EXCEPTION => 'onException']; }
    public function onException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        $status = $e instanceof ApiException ? $e->status : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);
        $message = $e instanceof ApiException ? $e->getMessage() : ($status === 500 ? 'Internal server error' : ResponseMessages::get($status));
        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];
        $event->setResponse(new JsonResponse(['error' => ['message' => $message, 'details' => (object) ($e instanceof ApiException ? $e->details : [])]], $status, $headers));
    }
}
