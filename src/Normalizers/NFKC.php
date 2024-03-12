<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/*
 * NFKC Normalizer.
 */

use function normalizer_normalize;

class NFKC extends Normalizer
{


    public function normalize(string $text): string
    {
        return normalizer_normalize($text, \Normalizer::NFKC);
    }
}