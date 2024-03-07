<?php

//declare(strict_types=1);
//
//namespace Codewithkyrian\Transformers\Generation\Streamers;
//
//use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;
//use InvalidArgumentException;
//use function Codewithkyrian\Transformers\Utils\timeUsage;
//
///**
// * Simple text streamer that prints the token(s) to stdout as soon as entire words are formed.
// */
//class TextStreamer extends Streamer
//{
//    protected string $printedText = '';
//    protected mixed $onStreamCallback = null;
//    protected mixed $onStreamEndCallback = null;
//    protected StreamMode $streamMode = StreamMode::PARTIAL;
//
//    public function __construct(protected PretrainedTokenizer $tokenizer)
//    {
//    }
//
//    public static function make(PretrainedTokenizer $tokenizer): self
//    {
//        return new static($tokenizer);
//    }
//
//    public function onStream(callable $callback): self
//    {
//        $this->onStreamCallback = $callback;
//        return $this;
//    }
//
//    public function onStreamEnd(callable $callback): self
//    {
//        $this->onStreamEndCallback = $callback;
//        return $this;
//    }
//
//    public function setStreamMode(StreamMode $streamMode): self
//    {
//        $this->streamMode = $streamMode;
//        return $this;
//    }
//
//    public function put(mixed $value): void
//    {
//        dump("beforePut" .timeUsage(sinceLastCall: true));
//        if (count($value) > 1) {
//            throw new InvalidArgumentException("TextStreamer only supports batch size 1");
//        }
//
//        $decodedText = $this->tokenizer->decode($value[0]['output_token_ids'], skipSpecialTokens: true);
//        $printedLength = mb_strlen($this->printedText);
//        $newText = mb_substr($decodedText, $printedLength);
//        $this->printedText .= $newText;
//
////        if ($this->onStreamCallback !== null) {
////            call_user_func(
////                $this->onStreamCallback,
////                $this->streamMode === StreamMode::PARTIAL ? $newText : $this->printedText
////            );
////        }
//
//        dump("afterPut" .timeUsage(sinceLastCall: true));
//    }
//
//    public function end(): void
//    {
//        if ($this->onStreamEndCallback !== null) {
//            call_user_func($this->onStreamEndCallback, $this->printedText);
//        }
//    }
//}


declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\Streamers;

use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;
use InvalidArgumentException;

/**
 * Simple text streamer that prints the token(s) to stdout as soon as entire words are formed.
 */
class TextStreamer extends Streamer
{
    protected string $printedText = '';
    protected mixed $onStreamCallback = null;
    protected mixed $onStreamEndCallback = null;
    protected StreamMode $streamMode = StreamMode::PARTIAL;
    protected int $printedLength = 0;
    protected int $lastDecodedCheckpointForToken = 0;
    protected int $lastDecodedCheckpointForText = 0;

    public function __construct(protected PretrainedTokenizer $tokenizer)
    {
    }

    public static function make(PretrainedTokenizer $tokenizer): self
    {
        return new static($tokenizer);
    }

    public function onStream(callable $callback): self
    {
        $this->onStreamCallback = $callback;
        return $this;
    }

    public function onStreamEnd(callable $callback): self
    {
        $this->onStreamEndCallback = $callback;
        return $this;
    }

    public function setStreamMode(StreamMode $streamMode): self
    {
        $this->streamMode = $streamMode;
        return $this;
    }

    public function put(mixed $value): void
    {
        if (count($value) > 1) {
            throw new InvalidArgumentException("TextStreamer only supports batch size 1");
        }

        $tokensToDecode = array_slice($value[0]['output_token_ids'], $this->lastDecodedCheckpointForToken);

        $decodedText = $this->tokenizer->decode($tokensToDecode, skipSpecialTokens: true);

        // Check for punctuation marks indicating the end of a word or sentence
        $punctuationMarks = ['.', ',', '!', '?', ';', ':'];


        $this->printedText = mb_substr($this->printedText, 0, $this->lastDecodedCheckpointForText)
            . ($this->lastDecodedCheckpointForToken == 0 ? '' : ' ')
            . $decodedText;

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
    }
}

