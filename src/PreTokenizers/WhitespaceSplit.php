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
        return explode(' ', $text);
    }
}