<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class ReplacePreTokenizer extends PreTokenizer
{

    protected ?string $pattern;
    protected string $content;
    public function __construct(array $config)
    {
        $this->pattern = $config['pattern'] ?? null;
        $this->content = $config['content'];
    }
    public function preTokenizeText(string|array $text, array $options): array
    {
        if($this->pattern === null)
        {
            return [$text];
        }

        return preg_replace($this->pattern, $this->content, $text);
    }
}