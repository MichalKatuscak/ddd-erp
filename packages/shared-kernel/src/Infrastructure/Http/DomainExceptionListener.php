<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Http;

use SharedKernel\Domain\UncaughtDomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class DomainExceptionListener
{
    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle MapRequestPayload validation failures → RFC 7807 with violations
        if ($exception instanceof UnprocessableEntityHttpException) {
            $previous = $exception->getPrevious();
            if ($previous instanceof ValidationFailedException) {
                $violations = [];
                foreach ($previous->getViolations() as $violation) {
                    $property = $violation->getPropertyPath();
                    $violations[$property][] = $violation->getMessage();
                }
                $event->setResponse(new JsonResponse([
                    'type'       => '/errors/validation',
                    'title'      => 'Validation Failed',
                    'status'     => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'violations' => $violations,
                ], Response::HTTP_UNPROCESSABLE_ENTITY));
                return;
            }
        }

        // Let non-validation HTTP exceptions pass through
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
            $event->setResponse(new JsonResponse([
                'type'   => '/errors/domain',
                'title'  => 'Request Failed',
                'status' => $exception->getStatusCode(),
                'detail' => $exception->getMessage(),
            ], $exception->getStatusCode(), $exception->getHeaders()));
            return;
        }

        // Exceptions marked as UncaughtDomainException should propagate as 500
        if ($exception instanceof UncaughtDomainException) {
            return;
        }

        if ($exception instanceof \DomainException) {
            $message = $exception->getMessage();

            $isNotFound = str_contains(strtolower($message), 'not found');
            $status = $isNotFound
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_UNPROCESSABLE_ENTITY;

            $type = $isNotFound ? '/errors/not-found' : '/errors/domain';
            $title = $isNotFound ? 'Resource Not Found' : 'Business Rule Violation';

            $event->setResponse(new JsonResponse([
                'type'   => $type,
                'title'  => $title,
                'status' => $status,
                'detail' => $message,
            ], $status));
        }
    }
}
