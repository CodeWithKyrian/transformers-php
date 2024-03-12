<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

class PreTokenizerSequence extends PreTokenizer
{
    /**
     * @var PreTokenizer[]
     */
    protected array $preTokenizers;

    public function __construct(array $config)
    {
        $this->preTokenizers = array_map(
            fn(array $config) => PreTokenizer::fromConfig($config),
            $config['pretokenizers']
        );
    }

    public function preTokenizeText(string|array $text, array $options): array
    {
        return array_reduce(
            $this->preTokenizers,
            fn($text, PreTokenizer $preTokenizer) => $preTokenizer->preTokenize($text, $options),
            [$text]
        );
    }
}