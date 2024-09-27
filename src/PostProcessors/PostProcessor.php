<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PostProcessors;

abstract class PostProcessor
{
    public function __construct(public array $config)
    {
    }

    /**
     * Factory method to create a PostProcessor object from a configuration object.
     *
     * @param array|null $config
     * @return self|null
     */
    public static function fromConfig(?array $config): ?self
    {
        if ($config === null) {
            return null;
        }

        return match ($config['type']) {
            'BertProcessing' => new BertProcessing($config),
            'ByteLevel' => new ByteLevelPostProcessor($config),
            'TemplateProcessing' => new TemplateProcessing($config),
            'RobertaProcessing' => new RobertaProcessing($config),
            'Sequence' => new PostProcessorSequence($config),
            default => throw new \InvalidArgumentException("Unknown post-processor type {$config['type']}"),
        };
    }

    /**
     * @param array $tokens The input tokens to be post-processed.
     * @param array|null $tokenPair The input tokens for the second sequence in a pair.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     * @return PostProcessedOutput
     */
    abstract public function postProcess(array $tokens, ?array $tokenPair = null,  bool $addSpecialTokens = true): PostProcessedOutput;

    public function __invoke(array $tokens, ...$args): PostProcessedOutput
    {
        return $this->postProcess($tokens, ...$args);
    }

}