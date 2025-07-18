<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use Psr\Log\AbstractLogger;

require_once './vendor/autoload.php';

$cacheDir = '/Volumes/KYRIAN SSD/Transformers';

class FileLogger extends AbstractLogger
{
    public function __construct(protected string $filename) {}

    public function log($level, $message, array $context = []): void
    {
        $line = sprintf("[%s][%s] %s %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message, empty($context) ? '' : json_encode($context));
        file_put_contents($this->filename, $line, FILE_APPEND);
    }
}

$logger = new FileLogger(__DIR__ . '/transformers.log');

Transformers::setup()
    ->setCacheDir($cacheDir)
    ->setLogger($logger)
    ->setImageDriver(ImageDriver::VIPS);
