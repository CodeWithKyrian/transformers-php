<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class SplitPreTokenizer extends PreTokenizer
{
    protected string $pattern;

    public function __construct(protected array $config)
    {
    }

    /**
     * Tokenizes text by splitting it using the given pattern.
     */
    public function preTokenizeText(string|array $text, array $options): array
    {
        // TODO: Consider $config['invert'] option
        return preg_split($this->config['pattern'], $text);
    }
}