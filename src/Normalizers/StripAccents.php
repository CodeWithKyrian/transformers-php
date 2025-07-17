<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Normalizers;

/**
 * StripAccents normalizer removes all accents from the text.
 */
class StripAccents extends Normalizer
{
    /**
     * Removes accents from the text.
     * @param string $text The text to remove accents from.
     * @return string The text with accents removed.
     */
    public function normalize(string $text): string
    {
        return preg_replace('/[\x{0300}-\x{036f}]/u', '', $text);
    }
}
