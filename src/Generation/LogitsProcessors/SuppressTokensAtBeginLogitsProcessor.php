<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

/**
 *  A LogitsProcessor that suppresses a list of tokens as soon as the `generate` function starts
 *  generating using `begin_index` tokens. This should ensure that the tokens defined by
 *  `begin_suppress_tokens` at not sampled at the beginning of the generation process.
 */
class SuppressTokensAtBeginLogitsProcessor extends LogitsProcessor
{
    public function __construct(
        protected array $beginSuppressTokens,
        protected int   $beginIndex = 0
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        for ($i = 0; $i < count($inputIds); $i++) {
            if (count($inputIds[$i]) === $this->beginIndex) {
                $batchLogits = $logits[$i];
                foreach ($this->beginSuppressTokens as $token) {
                    $batchLogits->buffer()[$token] = -INF;
                }
            }
        }
        
        return $logits;
    }
}