<?php

namespace App\EventListener;

use App\Service\OpenTelemetryService;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleTracingSubscriber implements EventSubscriberInterface
{
    private array $activeSpans = [];

    public function __construct(
        private readonly OpenTelemetryService $openTelemetryService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 1024],
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', -1024],
            ConsoleEvents::ERROR => ['onConsoleError', -1024],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }

        $commandName = $command->getName() ?? 'unknown';
        $input = $event->getInput();

        // Skip tracing for certain commands to avoid noise
        if ($this->shouldSkipCommand($commandName)) {
            return;
        }

        $spanName = sprintf('console.command %s', $commandName);

        $attributes = [
            'console.command' => $commandName,
            'console.arguments' => $this->getInputArguments($input),
        ];

        // Add options if any
        $options = $this->getInputOptions($input);
        if (!empty($options)) {
            $attributes['console.options'] = $options;
        }

        $span = $this->openTelemetryService->startSpan($spanName, $attributes);

        // Activate span in context so child spans are linked
        $scope = $span->activate();

        $this->activeSpans[spl_object_hash($command)] = [
            'span' => $span,
            'scope' => $scope,
            'start_time' => microtime(true)
        ];
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }

        $commandHash = spl_object_hash($command);
        if (!isset($this->activeSpans[$commandHash])) {
            return;
        }

        $spanData = $this->activeSpans[$commandHash];
        unset($this->activeSpans[$commandHash]);

        $span = $spanData['span'];
        $scope = $spanData['scope'];
        $executionTime = microtime(true) - $spanData['start_time'];

        $exitCode = $event->getExitCode();

        $span->setAttributes([
            'console.exit_code' => $exitCode,
            'console.execution_time' => $executionTime,
        ]);

        if ($exitCode !== 0) {
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, "Command failed with exit code $exitCode");
        }

        $scope->detach();
        $span->end();
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }

        $commandHash = spl_object_hash($command);
        if (!isset($this->activeSpans[$commandHash])) {
            return;
        }

        $spanData = $this->activeSpans[$commandHash];
        unset($this->activeSpans[$commandHash]);

        $span = $spanData['span'];
        $scope = $spanData['scope'];
        $error = $event->getError();

        $span->setAttributes([
            'error' => true,
            'error.type' => get_class($error),
            'error.message' => $error->getMessage(),
            'console.exit_code' => $event->getExitCode(),
        ]);

        $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $error->getMessage());

        $scope->detach();
        $span->end();
    }

    private function shouldSkipCommand(string $commandName): bool
    {
        // Skip debug and dev commands to reduce noise
        $skipPatterns = [
            'debug:',
            'cache:',
            'assets:',
            'lint:',
            'about',
            'list',
            'help',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_starts_with($commandName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getInputArguments($input): string
    {
        $arguments = [];
        foreach ($input->getArguments() as $key => $value) {
            if ($key !== 'command') { // Skip the command name itself
                $valueString = is_array($value) ? implode(',', $value) : (string) $value;
                $arguments[] = "$key=$valueString";
            }
        }
        return implode(' ', $arguments);
    }

    private function getInputOptions($input): string
    {
        $options = [];
        foreach ($input->getOptions() as $key => $value) {
            if ($value !== false && $value !== null && $value !== '') {
                if (is_bool($value)) {
                    $options[] = "--$key";
                } else {
                    $valueString = is_array($value) ? implode(',', $value) : (string) $value;
                    $options[] = "--$key=$valueString";
                }
            }
        }
        return implode(' ', $options);
    }
}