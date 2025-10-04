<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Centralized error/critical logger.
 *
 * Listens to HTTP kernel and Console errors and logs them once.
 * In production, Monolog routes error/critical logs to Slack via the configured handler.
 */
class ErrorLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $request = $event->getRequest();
        $context = $this->buildHttpContext($request, $throwable);

        $level = $this->determineLevelFromThrowable($throwable);

        if ($level === 'critical') {
            $this->logger->critical($throwable->getMessage(), $context);
        } else {
            $this->logger->error($throwable->getMessage(), $context);
        }
    }

    private function buildHttpContext(Request $request, \Throwable $throwable): array
    {
        return [
            'scope' => 'http',
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'route' => $request->attributes->get('_route'),
            'client_ip' => $request->getClientIp(),
            'query' => $request->query->all(),
            'request' => $request->request->all(),
            'env' => $_SERVER['APP_ENV'] ?? null,
            'exception' => $throwable,
        ];
    }

    private function determineLevelFromThrowable(\Throwable $e): string
    {
        // If it's an HttpException, treat 5xx as critical and others as error
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            return $status >= 500 ? 'critical' : 'error';
        }

        // For all other exceptions, use critical as they usually signal 500
        return 'critical';
    }
}
