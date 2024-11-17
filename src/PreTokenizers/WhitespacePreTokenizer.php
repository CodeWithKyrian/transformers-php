<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTokenizers;

/**
 * Splits on word boundaries (using the following regular expression: `\w+|[^\w\s]+`).
 */
class WhitespacePreTokenizer extends PreTokenizer
{

    protected function preTokenizeText(array|string $text, array $options): array
    {
        preg_match_all('/[\p{N}\p{L}]+|[^\p{Z}\s]+/u', $text, $matches);

        return $matches[0] ?? [];
    }
}
