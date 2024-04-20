<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\Streamers;

class StdOutStreamer extends TextStreamer
{
    public function __construct(StreamMode $streamMode = StreamMode::PARTIAL)
    {
        parent::__construct();

        $this->setStreamMode($streamMode);
        if ($streamMode === StreamMode::FULL) {
            $this->onStreamEnd(fn(string $full) => $this->echoToConsole($full, true));
        } else {
            $this->onStream(fn(string $partial) => $this->echoToConsole($partial));
            $this->onStreamEnd(fn(string $full) => $this->echoToConsole('', true));
        }
    }

    private function echoToConsole(string $text, bool $newLine = false): void
    {
        echo $text;
        if ($newLine) {
            echo PHP_EOL;
        }
    }
}