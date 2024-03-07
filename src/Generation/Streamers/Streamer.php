<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\Streamers;

/**
 * Base streamer from which all streamers inherit.
 */
abstract class Streamer
{
    abstract public function put(mixed $value): void;

    abstract public function end(): void;

}