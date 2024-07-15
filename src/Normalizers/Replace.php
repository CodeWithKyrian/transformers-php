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

        if ($pattern === null) {
            return $text;
        }

        $regex = $pattern['Regex'] ?? null;
        $string = $pattern['String'] ?? null;
        $replacement = $this->config['content'] ?? '';

        if ($regex !== null) {
            return preg_replace("/{$regex}/u", $replacement, $text);
        }

        if ($string !== null) {
            return str_replace($string, $replacement, $text);
        }

        return $text;
    }
}