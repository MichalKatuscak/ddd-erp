<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class DomainExceptionListener
{
    #[AsEventListener]
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Let Symfony HTTP exceptions pass through
        if ($exception instanceof HttpExceptionInterface) {
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
