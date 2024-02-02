<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/**
 * A normalizer that strips leading and/or trailing whitespace from the input text.
 */
class StripNormalizer extends Normalizer
{


    /**
     * Strip leading and/or trailing whitespace from the input text.
     * @param string $text The input text.
     * @return string The normalized text.
     */
    public function normalize(string $text): string
    {
        if ($this->config['strip_left'] && $this->config['strip_right']) {
            // Fast path to avoid an extra trim call
            $text = trim($text);
        } else {
            if ($this->config['strip_left']) {
                $text = ltrim($text);
            }
            if ($this->config['strip_right']) {
                $text = rtrim($text);
            }
        }
        return $text;
    }
}