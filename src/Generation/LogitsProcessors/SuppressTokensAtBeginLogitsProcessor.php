<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

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
    public function __invoke(array $inputIds, Tensor|NDArrayPhp &$logits): Tensor|NDArrayPhp
    {
        if (count($inputIds) == $this->beginIndex) {
            foreach ($this->beginSuppressTokens as $token) {
                $logits->buffer()[$token] = -INF;
            }
        }

        return $logits;
    }
}