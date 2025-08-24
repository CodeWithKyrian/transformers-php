<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PostProcessors;

/**
 * A PostProcessor that returns the given tokens as is.
 */
class ByteLevelPostProcessor extends PostProcessor
{

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Post process the given tokens.
     * @param string[] $tokens The input tokens.
     * @param string[]|null $tokenPair The input tokens for the second sequence in a pair.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     * @return PostProcessedOutput
     */
    public function postProcess(array $tokens, ?array $tokenPair = null, bool $addSpecialTokens = true): PostProcessedOutput
    {
        if ($tokenPair !== null) {
            $tokens = array_merge($tokens, $tokenPair);
        }

        return new PostProcessedOutput($tokens, array_fill(0, count($tokens), 0));
    }
}
