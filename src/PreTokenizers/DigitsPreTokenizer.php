<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class DigitsPreTokenizer extends PreTokenizer
{

    protected string $pattern;

    public function __construct(protected array $config)
    {
        $individualDigits = $this->config['individual_digits'] ? '' : '+';

        $digitPattern = "[\D]+|\d$individualDigits";

        $this->pattern = "/$digitPattern/u";
    }

    public function preTokenizeText(string|array $text, array $options): array
    {
        preg_match_all($this->pattern, $text, $matches, PREG_SPLIT_NO_EMPTY);

        return $matches[0] ?? [];
    }
}
