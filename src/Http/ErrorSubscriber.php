<?php
declare(strict_types=1);
namespace App\Http;
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
        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        $event->setResponse(new JsonResponse(['error' => ['message' => $status === 500 ? 'Internal server error' : (ResponseMessages::get($status))]], $status));
    }
}
