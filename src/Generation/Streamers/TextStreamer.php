<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\Streamers;

use Codewithkyrian\Transformers\PreTrainedTokenizers\PreTrainedTokenizer;
use InvalidArgumentException;

/**
 * Simple text streamer that prints the token(s) to stdout as soon as entire words are formed.
 */
class TextStreamer extends Streamer
{
    protected string $printedText = '';
    protected StreamMode $streamMode = StreamMode::PARTIAL;
    protected int $printedLength = 0;
    protected int $lastDecodedCheckpointForToken = 0;
    protected int $lastDecodedCheckpointForText = 0;

    public static function make(): static
    {
        $streamer = parent::make();

        $streamer->onStreamCallback ??= function ($value) {
            echo $value;
        };

        $streamer->onStreamEndCallback ??= function () {
            echo PHP_EOL;
        };

        return $streamer;
    }

    public function put(mixed $value): void
    {
        if (count($value) > 1) {
            throw new InvalidArgumentException("TextStreamer only supports batch size 1");
        }

        if ($this->skipPrompt && $this->nextTokensArePrompt) {
            $this->nextTokensArePrompt = false;
//            $this->printedText = $this->tokenizer->decode($this->promptTokens, skipSpecialTokens: true);
//            $this->printedLength = mb_strlen($this->printedText);
//            $this->lastDecodedCheckpointForToken = count($this->promptTokens) - 1;
//            $this->lastDecodedCheckpointForText = mb_strlen($this->printedText);
//            return;
            $prompt = $this->tokenizer->decode($this->promptTokens, skipSpecialTokens: true);
            $this->printedLength = mb_strlen($prompt);
            $this->lastDecodedCheckpointForToken = count($this->promptTokens) - 1;
            return;
        }

        $tokensToDecode = array_slice($value[0]['output_token_ids'], $this->lastDecodedCheckpointForToken);

        if (empty($tokensToDecode)) return;

        $decodedText = $this->tokenizer->decode($tokensToDecode, skipSpecialTokens: true);

        // Check for punctuation marks indicating the end of a word or sentence
        $punctuationMarks = ['.', ',', '!', '?', ';', ':'];

        $this->printedText = mb_substr($this->printedText, 0, $this->lastDecodedCheckpointForText)
            .($this->lastDecodedCheckpointForToken == 0 ? '' : ' ')
            .$decodedText;

        $newText = mb_substr($this->printedText, $this->printedLength);

        $this->printedLength = mb_strlen($this->printedText);

        if (in_array(mb_substr($decodedText, -1), $punctuationMarks)) {
            $this->lastDecodedCheckpointForToken = count($value[0]['output_token_ids']);
            $this->lastDecodedCheckpointForText = mb_strlen($this->printedText);
        }

        if ($this->onStreamCallback !== null) {
            call_user_func(
                $this->onStreamCallback,
                $this->streamMode === StreamMode::PARTIAL ? $newText : $this->printedText
            );
        }
    }

    public function end(): void
    {
        if ($this->onStreamEndCallback !== null) {
            call_user_func($this->onStreamEndCallback, $this->printedText);
        }

        $this->printedText = '';
        $this->printedLength = 0;
        $this->lastDecodedCheckpointForToken = 0;
        $this->lastDecodedCheckpointForText = 0;
    }
}
