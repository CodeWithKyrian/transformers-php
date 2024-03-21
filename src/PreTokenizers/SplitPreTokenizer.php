<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class SplitPreTokenizer extends PreTokenizer
{
    protected string|array $pattern;

    public function __construct(protected array $config)
    {
        $this->pattern = $config['pattern'];
    }

    /**
     * Tokenizes text by splitting it using the given pattern.
     */
    public function preTokenizeText(string|array $text, array $options): array
    {
        if (is_string($this->pattern)) {
            return explode($this->pattern, $text);
        }

        $regex = $this->pattern['Regex'] ?? null;

        if($regex != null)
        {
           $split =  preg_split($regex, $text, -1, PREG_SPLIT_NO_EMPTY);
           dd($split);
        }

        // TODO: Handle all types of Regex
        return $text;
    }
}