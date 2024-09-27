<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PostProcessors;

/**
 * A post-processor that applies multiple post-processors in sequence.
 */
class PostProcessorSequence extends PostProcessor
{

    /**
     * List of post-processors to apply.
     */
    protected array $processors;

    /**
     * Creates a new instance of PostProcessorSequence.
     *
     * @param array $config The configuration array.
     *  - 'processors' (array): The list of post-processors to apply.
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->processors = array_map(
            fn ($processorConfig) => PostProcessor::fromConfig($processorConfig),
            $config['processors']
        );
    }

    /**
     * Post-process the given tokens.
     *
     * @param array $tokens The list of tokens for the first sequence.
     * @param string[]|null $tokenPair The input tokens for the second sequence in a pair.
     * * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     *
     * @return PostProcessedOutput An array containing the post-processed tokens and token_type_ids.
     */
    public function postProcess(array $tokens, ?array $tokenPair = null, bool $addSpecialTokens = true): PostProcessedOutput
    {
        $tokenTypeIds = null;

        foreach ($this->processors as $processor) {
            if ($processor instanceof ByteLevelPostProcessor) {
                // Special case where we need to pass the tokens_pair to the post-processor
                $output = $processor->postProcess($tokens);
                $tokens = $output->tokens;

                if ($tokenPair !== null) {
                    $pairOutput = $processor->postProcess($tokenPair);
                    $tokenPair = $pairOutput->tokens;
                }
            } else {
                $output = $processor->postProcess($tokens, $tokenPair, $addSpecialTokens);
                $tokens = $output->tokens;
                $tokenTypeIds = $output->tokenTypeIds;
            }
        }

        return new PostProcessedOutput($tokens, $tokenTypeIds);
    }
}

?>