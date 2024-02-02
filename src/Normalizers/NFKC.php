<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/*
 * NFKC Normalizer.
 */
class NFKC extends Normalizer
{


    public function normalize(string $text): string
    {
        return \Normalizer::normalize($text, \Normalizer::NFKC);
    }
}