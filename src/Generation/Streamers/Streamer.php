<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\Streamers;

use Codewithkyrian\Transformers\PreTrainedTokenizers\PreTrainedTokenizer;
use DateTime;

/**
 * Base streamer from which all streamers inherit.
 */
abstract class Streamer
{
    protected bool $skipPrompt = false;
    protected bool $nextTokensArePrompt = true;

    protected PreTrainedTokenizer $tokenizer;
    protected mixed $onStreamCallback = null;
    protected mixed $onStreamEndCallback = null;
    protected StreamMode $streamMode = StreamMode::PARTIAL;

    protected float $startTime;
    protected float $tokensPerSecond = 0;
    protected int $totalTokensProcessed = 0;

    public static function make(): static
    {
        return new static();
    }

    public function setTokenizer(PreTrainedTokenizer $tokenizer): static
    {
        $this->tokenizer = $tokenizer;
        return $this;
    }

    public function shouldSkipPrompt(bool $skipPrompt = true): static
    {
        $this->skipPrompt = $skipPrompt;
        return $this;
    }

    public function onStream(callable $callback): static
    {
        $this->onStreamCallback = $callback;
        return $this;
    }

    public function onStreamEnd(callable $callback): static
    {
        $this->onStreamEndCallback = $callback;
        return $this;
    }

    public function setStreamMode(StreamMode $streamMode): static
    {
        $this->streamMode = $streamMode;
        return $this;
    }

    abstract public function put(mixed $value): void;

    abstract public function end(): void;

    public function getTPS(): float
    {
        return $this->tokensPerSecond;
    }

}
