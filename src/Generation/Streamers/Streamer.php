<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\Streamers;

use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;

/**
 * Base streamer from which all streamers inherit.
 */
abstract class Streamer
{
    abstract public function init(PretrainedTokenizer $tokenizer, array $inputTokens, bool $excludeInput = false): void;

    abstract public function put(mixed $value): void;

    abstract public function end(): void;

}