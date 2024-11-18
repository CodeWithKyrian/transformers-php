<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class StreamLogger implements LoggerInterface
{
    use LoggerTrait;

    protected const LOG_FORMAT = "[%datetime%] %level_name%: %message% %context%\n";
    protected const DATE_FORMAT = "Y-m-d\TH:i:sP";


    /**
     * @param resource $stream
     */
    public function __construct(protected $stream = STDOUT) {}


    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $params = [
            '%datetime%' => date(static::DATE_FORMAT),
            '%level_name%' => $level,
            '%message%' => $message,
            '%context%' => json_encode(
                $context,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE |
                JSON_PRESERVE_ZERO_FRACTION
            ),
        ];

        fwrite($this->stream, strtr(static::LOG_FORMAT, $params));
    }
}
