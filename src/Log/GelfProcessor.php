<?php

namespace App\Log;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;

class GelfProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly string $serviceName,
        private readonly string $environment,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        // Add md5 hash of the message
        $record->extra['md5'] = md5($record->message);

        // Add trace ID from OpenTelemetry if available
        $span = Span::fromContext(Context::getCurrent());
        if ($span->isRecording()) {
            $spanContext = $span->getContext();
            if ($spanContext->isValid()) {
                $record->extra['traceid'] = $spanContext->getTraceId();
                $record->extra['spanid'] = $spanContext->getSpanId();
            }
        }

        // Add timestamp in ISO format for better readability
        $record->extra['timestamp_iso'] = date('c', $record->datetime->getTimestamp());

        // Add service information
        $record->extra['service'] = $this->serviceName;
        $record->extra['environment'] = $this->environment;

        return $record;
    }
}