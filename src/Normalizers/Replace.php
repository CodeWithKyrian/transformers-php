<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/**
 * Replace normalizer that replaces occurrences of a pattern with a given string or regular expression.
 */
class Replace extends Normalizer
{

    public function normalize(string $text): string
    {
        $pattern = $this->config['pattern'] ?? null;

        if ($pattern != null) {
            $text = preg_replace($pattern, $this->config['content'], $text);
        }

        return $text;
    }
}