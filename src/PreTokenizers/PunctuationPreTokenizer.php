<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class PunctuationPreTokenizer extends PreTokenizer
{
    protected string $pattern;
    public function __construct(protected array $config)
    {
        $punctuationRegex = '\p{P}\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7E';
        $this->pattern = "/\s+|([$punctuationRegex])+/u";
    }
    public function preTokenizeText(string|array $text, array $options): array
    {
        return preg_split($this->pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?? [];
    }
}