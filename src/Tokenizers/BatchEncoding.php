<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

/**
 * Holds the output of the tokenizer's call function.
 */
class BatchEncoding
{
    /**
     * @param int[]|int[][] $inputIds List of token ids to be fed to a model.
     * @param int[]|int[][] $attentionMask List of indices specifying which tokens should be attended to by the model.
     * @param int[]|int[][] $tokenTypeIds List of token type ids to be fed to a model.
     */
    public function __construct(
        public readonly array $inputIds,
        public readonly ?array $attentionMask = null,
        public readonly ?array $tokenTypeIds = null,
    )
    {
    }
}