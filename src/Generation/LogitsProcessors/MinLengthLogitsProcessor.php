<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

/**
 * A logits processor that enforces a minimum number of tokens.
 */
class MinLengthLogitsProcessor extends LogitsProcessor
{

    /**
     * @param int $minLength The minimum length below which the score of `eos_token_id` is set to negative infinity.
     * @param int|array $eosTokenId he ID/IDs of the end-of-sequence token.
     */
    public function __construct(
        protected int $minLength,
        protected int|array $eosTokenId,
    )
    {
        if(!is_array($eosTokenId)){
            $this->eosTokenId = [$eosTokenId];
        }
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $inputIds, Tensor|NDArrayPhp &$logits): Tensor|NDArrayPhp
    {
        if (count($inputIds) < $this->minLength) {
            foreach ($this->eosTokenId as $id) {
                $logits->buffer()[$id] = -INF;
            }
        }
        return $logits;
    }
}