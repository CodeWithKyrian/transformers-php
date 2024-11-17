<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class PunctuationPreTokenizer extends PreTokenizer
{
    protected string $pattern;
    public function __construct(protected array $config)
    {
        $PUNCTUATION_REGEX = '\p{P}\x{0021}-\x{002F}\x{003A}-\x{0040}\x{005B}-\x{0060}\x{007B}-\x{007E}';
        $this->pattern = "/[^{$PUNCTUATION_REGEX}]+|[{$PUNCTUATION_REGEX}]+/u";
    }
    public function preTokenizeText(string|array $text, array $options): array
    {
        preg_match_all($this->pattern, $text, $matches);
        return $matches[0];
    }
}
