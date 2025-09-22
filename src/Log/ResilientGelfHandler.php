<?php

namespace App\Log;

use Gelf\Message;
use Gelf\Publisher;
use Gelf\Transport\UdpTransport;
use Monolog\Handler\GelfHandler;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;
use Monolog\Level;

class ResilientGelfHandler extends GelfHandler
{
    private bool $isConnected = true;
    private int $lastFailTime = 0;
    private int $retryInterval = 300; // 5 minutes

    public function __construct(
        Publisher $publisher,
        int $level = Level::Info->value,
        bool $bubble = true,
        private readonly ?LoggerInterface $fallbackLogger = null,
        private readonly int $connectTimeout = 3,
        private readonly int $timeout = 5,
    ) {
        parent::__construct($publisher, $level, $bubble);
        $this->configurePublisherTimeout();
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->shouldAttemptConnection()) {
            $this->logToFallback($record);
            return;
        }

        try {
            parent::write($record);
            $this->markConnectionSuccessful();
        } catch (\Throwable $e) {
            $this->markConnectionFailed();
            $this->logConnectionError($e);
            $this->logToFallback($record);
        }
    }

    private function shouldAttemptConnection(): bool
    {
        if ($this->isConnected) {
            return true;
        }

        $now = time();
        if ($now - $this->lastFailTime >= $this->retryInterval) {
            $this->isConnected = true;
            return true;
        }

        return false;
    }

    private function markConnectionSuccessful(): void
    {
        if (!$this->isConnected) {
            $this->isConnected = true;
            $this->fallbackLogger?->info('GELF connection restored');
        }
    }

    private function markConnectionFailed(): void
    {
        $this->isConnected = false;
        $this->lastFailTime = time();
    }

    private function logConnectionError(\Throwable $e): void
    {
        $this->fallbackLogger?->warning('GELF handler failed, falling back to alternative logging: {error}', [
            'error' => $e->getMessage(),
            'next_retry' => date('Y-m-d H:i:s', $this->lastFailTime + $this->retryInterval),
        ]);
    }

    private function logToFallback(LogRecord $record): void
    {
        if ($this->fallbackLogger === null) {
            return;
        }

        $level = strtolower($record->level->name);
        $message = $record->message;
        $context = array_merge($record->context, $record->extra);

        match ($level) {
            'emergency' => $this->fallbackLogger->emergency($message, $context),
            'alert' => $this->fallbackLogger->alert($message, $context),
            'critical' => $this->fallbackLogger->critical($message, $context),
            'error' => $this->fallbackLogger->error($message, $context),
            'warning' => $this->fallbackLogger->warning($message, $context),
            'notice' => $this->fallbackLogger->notice($message, $context),
            'info' => $this->fallbackLogger->info($message, $context),
            'debug' => $this->fallbackLogger->debug($message, $context),
            default => $this->fallbackLogger->info($message, $context),
        };
    }

    private function configurePublisherTimeout(): void
    {
        try {
            $reflection = new \ReflectionClass($this);
            $publisherProperty = $reflection->getParentClass()->getProperty('publisher');
            $publisherProperty->setAccessible(true);
            $publisher = $publisherProperty->getValue($this);

            if ($publisher instanceof Publisher) {
                $transports = $publisher->getTransports();
                if (!empty($transports)) {
                    $transport = $transports[0];

                    if ($transport instanceof UdpTransport) {
                        $transportReflection = new \ReflectionClass($transport);

                        if ($transportReflection->hasProperty('timeout')) {
                            $timeoutProperty = $transportReflection->getProperty('timeout');
                            $timeoutProperty->setAccessible(true);
                            $timeoutProperty->setValue($transport, $this->timeout);
                        }

                        if ($transportReflection->hasProperty('connectTimeout')) {
                            $connectTimeoutProperty = $transportReflection->getProperty('connectTimeout');
                            $connectTimeoutProperty->setAccessible(true);
                            $connectTimeoutProperty->setValue($transport, $this->connectTimeout);
                        }
                    }
                }
            }
        } catch (\ReflectionException $e) {
            // Silently ignore reflection errors, timeouts will use defaults
        }
    }
}