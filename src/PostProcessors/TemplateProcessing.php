<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PostProcessors;

/**
 * Post processor that replaces special tokens in a template with actual tokens.
 */
class TemplateProcessing extends PostProcessor
{

    /**
     * @var array The template for a single sequence of tokens.
     */
    public array $single;

    /**
     * @var array The template for a pair of sequences of tokens.
     */
    protected array $pair;


    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->single = $config['single'];
        $this->pair = $config['pair'];
    }

    /**
     * Replaces special tokens in the template with actual tokens.
     * @param string[] $tokens The input tokens.
     * @param string[]|null $tokenPair The input tokens for the second sequence in a pair.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     * @return PostProcessedOutput
     */
    public function postProcess(array $tokens, array $tokenPair = null,  bool $addSpecialTokens = true): PostProcessedOutput
    {
        $type = $tokenPair === null ? $this->single : $this->pair;

        $processedTokens = [];
        $types = [];

        foreach ($type as $item) {
            if (isset($item['SpecialToken'])) {
                if ($addSpecialTokens) {
                    $processedTokens[] = $item['SpecialToken']['id'];
                    $types[] = $item['SpecialToken']['type_id'];
                }
            } elseif (isset($item['Sequence'])) {
                if ($item['Sequence']['id'] === 'A') {
                    $processedTokens = array_merge($processedTokens, $tokens);
                    $types = array_merge($types, array_fill(0, count($tokens), $item['Sequence']['type_id']));
                } elseif ($item['Sequence']['id'] === 'B') {
                    $processedTokens = array_merge($processedTokens, $tokenPair);
                    $types = array_merge($types, array_fill(0, count($tokenPair), $item['Sequence']['type_id']));
                }
            }
        }

        return new PostProcessedOutput($processedTokens, $types);
    }
}