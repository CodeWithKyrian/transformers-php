<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class DigitsPreTokenizer extends PreTokenizer
{

    protected string $pattern;
    public function __construct(protected array $config)
    {
        $individualDigits = $this->config['individual_digits'] ? '' : '+';
        $digitPattern = "[^\\d]+|\\d$individualDigits";

        $this->pattern = "/$digitPattern/u";

    }
    public function preTokenizeText(string|array $text, array $options): array
    {
        return preg_split($this->pattern, $text, -1, PREG_SPLIT_NO_EMPTY) ?? [];
    }
}