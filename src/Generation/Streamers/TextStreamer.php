<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\Streamers;

use Codewithkyrian\Transformers\PreTrainedTokenizers\PreTrainedTokenizer;
use Codewithkyrian\Transformers\Tokenizers\TokenizerModel;
use DateTime;
use InvalidArgumentException;

/**
 * Simple text streamer that prints the token(s) to stdout as soon as entire words are formed.
 */
class TextStreamer extends Streamer
{
    private array $tokenCache = [];
    private int $printLen = 0;

    public static function make(): static
    {
        $streamer = parent::make();

        $streamer->onStreamCallback ??= function ($value) {
            fwrite(STDOUT, $value);
        };

        $streamer->onStreamEndCallback ??= function () {
            fwrite(STDOUT, PHP_EOL);
        };

        return $streamer;
    }

    public function put(mixed $value): void
    {
        if (count($value) > 1) {
            throw new \Exception('TextStreamer only supports batch size of 1');
        }

        if (!isset($this->startTime)) {
            $this->startTime = microtime(true);
        }

        if ($this->skipPrompt && $this->nextTokensArePrompt) {
            $this->nextTokensArePrompt = false;
            return;
        }

        $tokens = $value[0];
        $this->totalTokensProcessed += count($tokens);

        // Add the new token to the cache and decode the entire thing
        $this->tokenCache = array_merge($this->tokenCache, $tokens);
        $text = $this->tokenizer->decode($this->tokenCache, true);

        if (str_ends_with($text, "\n")) {
            // After the symbol for a new line, flush the cache.
            $printableText = substr($text, $this->printLen);
            $this->tokenCache = [];
            $this->printLen = 0;
        } elseif (strlen($text) > 0 && TokenizerModel::isChineseChar(ord($text[strlen($text) - 1]))) {
            // If the last token is a CJK character, print the characters.
            $printableText = substr($text, $this->printLen);
            $this->printLen += strlen($printableText);
        } else {
            // Otherwise, print until the last space char (simple heuristic)
            $lastSpaceIndex = strrpos($text, ' ');
            $printableText = substr($text, $this->printLen, $lastSpaceIndex + 1);
            $this->printLen += strlen($printableText);
        }

        $elapsedTime = microtime(true) - $this->startTime;

        if ($elapsedTime > 0) {
            $this->tokensPerSecond = $this->totalTokensProcessed / $elapsedTime;
        }

        if (strlen($printableText) > 0) {
            call_user_func($this->onStreamCallback, $printableText);
        }
    }

    public function end(): void
    {
        $printableText = '';
        if (count($this->tokenCache) > 0) {
            $text = $this->tokenizer->decode($this->tokenCache, true);
            $printableText = substr($text, $this->printLen);
            $this->tokenCache = [];
            $this->printLen = 0;
        }

        $this->nextTokensArePrompt = true;

        if (strlen($printableText) > 0) {
            call_user_func($this->onStreamCallback, $printableText);
        }

        call_user_func($this->onStreamEndCallback);
    }
}
