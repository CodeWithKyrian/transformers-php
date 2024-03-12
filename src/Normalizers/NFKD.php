<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Normalizers;

use function normalizer_normalize;

/**
 * Normalizes a string to Normalization Form Compatibility Decomposition (NFKD).

 */
class NFKD extends Normalizer
{


    public function normalize(string $text): string
    {
        return normalizer_normalize($text, \Normalizer::NFKD);
    }
}