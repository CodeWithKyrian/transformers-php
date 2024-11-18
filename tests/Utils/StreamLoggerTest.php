<?php

use Codewithkyrian\Transformers\Utils\StreamLogger;

beforeEach(function () {
    $this->outputBuffer = fopen('php://memory', 'rw');
    $this->logger = new StreamLogger($this->outputBuffer);
});

afterEach(function () {
    fclose($this->outputBuffer);
});

it('logs messages with the correct format', function () {
    $this->logger->log('info', 'This is a test message');

    rewind($this->outputBuffer);
    $output = stream_get_contents($this->outputBuffer);

    expect($output)->toMatch('/\[\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\+\-]\d{2}:\d{2}\] info: This is a test message \[\]\n/');
});

it('handles context correctly in log messages', function () {
    $context = ['user_id' => 123, 'action' => 'login'];
    $this->logger->log('warning', 'User action recorded', $context);

    rewind($this->outputBuffer);
    $output = stream_get_contents($this->outputBuffer);

    $expectedContext = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

    expect($output)->toContain('warning: User action recorded')
        ->and($output)->toContain($expectedContext);
});

it('handles different log levels correctly', function () {
    $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    foreach ($levels as $level) {
        $this->logger->log($level, "Message at $level level");

        rewind($this->outputBuffer);
        $output = stream_get_contents($this->outputBuffer);
        expect($output)->toContain("$level: Message at $level level");

        ftruncate($this->outputBuffer, 0); // Clear buffer
        rewind($this->outputBuffer);
    }
});

it('handles empty context gracefully', function () {
    $this->logger->log('info', 'Message with no context');

    rewind($this->outputBuffer);
    $output = stream_get_contents($this->outputBuffer);

    expect($output)->toContain('info: Message with no context []');
});

it('handles stringable objects in the message', function () {
    $stringable = new class {
        public function __toString(): string
        {
            return 'Stringable message content';
        }
    };

    $this->logger->log('info', $stringable);

    rewind($this->outputBuffer);
    $output = stream_get_contents($this->outputBuffer);

    expect($output)->toContain('info: Stringable message content');
});

it('outputs log messages to STDOUT', function () {
    $this->logger->log('info', 'Check output redirection');

    rewind($this->outputBuffer);
    $output = stream_get_contents($this->outputBuffer);

    expect($output)->toContain('info: Check output redirection');
});
