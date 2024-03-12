<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Normalizers;

use function normalizer_normalize;
/**
 * A normalizer that applies Unicode normalization form C (NFC) to the input text.
 */
class NFC extends Normalizer
{


    public function normalize(string $text): string
    {
        return normalizer_normalize($text, \Normalizer::NFC);
    }
}