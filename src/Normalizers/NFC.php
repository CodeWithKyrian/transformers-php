<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/**
 * A normalizer that applies Unicode normalization form C (NFC) to the input text.
 */
class NFC extends Normalizer
{


    public function normalize(string $text): string
    {
        return \Normalizer::normalize($text, \Normalizer::NFC);
    }
}