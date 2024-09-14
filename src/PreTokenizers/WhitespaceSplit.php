<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class WhitespaceSplit extends PreTokenizer
{

    public function __construct(protected array $config)
    {
    }

    public function preTokenizeText(string|array $text, array $options): array
    {
//        $words = preg_split('/\s+/', $text, flags: PREG_SPLIT_NO_EMPTY);
        return preg_split('/[\s\x{FFFD}]+/u', $text, flags: PREG_SPLIT_NO_EMPTY);
    }
}