<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

use Codewithkyrian\Transformers\Tokenizers\TokenizerModel;

/**
 * StripAccents normalizer removes all accents from the text.
 */
class StripAccents extends Normalizer
{

    public function normalize(string $text): string
    {
        return TokenizerModel::removeAccents($text);
    }
}
