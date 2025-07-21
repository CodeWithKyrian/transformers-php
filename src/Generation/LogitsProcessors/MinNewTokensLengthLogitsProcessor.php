<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

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
        for ($i = 0; $i < count($inputIds); $i++) {
            $newTokensLength = count($inputIds[$i]) - $this->promptLengthToSkip;

            if ($newTokensLength < $this->minNewTokens) {
                $batchLogits = $logits[$i];
                
                foreach ($this->eosTokenId as $eosTokenId) {
                    $batchLogits->buffer()[$eosTokenId] = -INF;
                }
            }
        }

        return $logits;
    }
}