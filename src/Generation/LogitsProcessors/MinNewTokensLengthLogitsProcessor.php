<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

class MinNewTokensLengthLogitsProcessor extends LogitsProcessor
{

    public function __construct(
        protected int $promptLengthToSkip,
        protected int $minNewTokens,
        protected int|array $eosTokenId,
    )
    {
        $this->eosTokenId = is_array($eosTokenId) ? $eosTokenId : [$eosTokenId];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        $newTokensLength = count($inputIds) - $this->promptLengthToSkip;

        if ($newTokensLength < $this->minNewTokens) {
            foreach ($this->eosTokenId as $eosTokenId) {
                $logits->buffer()[$eosTokenId] = -INF;
            }
        }

        return $logits;
    }
}