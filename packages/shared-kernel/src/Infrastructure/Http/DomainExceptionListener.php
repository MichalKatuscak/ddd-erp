<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Http;

use SharedKernel\Domain\UncaughtDomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class DomainExceptionListener
{
    #[AsEventListener]
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Let Symfony HTTP exceptions pass through (but not wrapped ones — handle below)
        if ($exception instanceof HttpExceptionInterface) {
            return;
        }

        // Unwrap Messenger HandlerFailedException to get the original domain exception
        if ($exception instanceof HandlerFailedException) {
            $nested = $exception->getWrappedExceptions();
            if (!empty($nested)) {
                $exception = reset($nested);
            }
        }

        // Domain exceptions that declare their own HTTP status code (e.g. 401)
        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                $exception->getStatusCode(),
                $exception->getHeaders(),
            ));
            return;
        }

        // Exceptions marked as UncaughtDomainException should propagate as 500
        if ($exception instanceof UncaughtDomainException) {
            return;
        }

        if ($exception instanceof \DomainException) {
            $message = $exception->getMessage();

            // CustomerNotFoundException → 404
            $status = str_contains($message, 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_UNPROCESSABLE_ENTITY;

            $event->setResponse(new JsonResponse(
                ['error' => $message],
                $status,
            ));
        }
    }
}
