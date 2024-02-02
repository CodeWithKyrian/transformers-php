<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/**
 * A Normalizer that lowercases the input string.
 */
class Lowercase extends Normalizer
{


    public function normalize(string $text): string
    {
        return mb_strtolower($text);
    }
}