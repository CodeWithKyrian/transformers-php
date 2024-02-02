<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/**
 * A Normalizer that prepends a string to the input string.
 */
class Prepend extends Normalizer
{

    /**
     *  Prepends the input string.
     */

    public function normalize(string $text): string
    {
        return $this->config['prepend'] . $text;
    }
}