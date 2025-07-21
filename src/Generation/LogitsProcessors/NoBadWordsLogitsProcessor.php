<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

class NoBadWordsLogitsProcessor extends LogitsProcessor
{

    protected array $badWordsIds;
    protected int|array $eosTokenId;

    public function __construct(array $badWordsIds, $eosTokenId)
    {
        $this->badWordsIds = $badWordsIds;
        $this->eosTokenId = is_array($eosTokenId) ? $eosTokenId : [$eosTokenId];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        for ($i = 0; $i < count($inputIds); $i++) {
            $batchLogits = $logits[$i];
            $ids = $inputIds[$i];

            foreach ($this->badWordsIds as $badWordIds) {
                // There aren't enough tokens to match the banned sequence
                if (count($ids) < count($badWordIds) - 1) {
                    continue;
                }

                // Whether to modify the logits of the last token in the bad word id sequence
                $mark = true;

                // For each bad word in the list, if the current sequence of input ids ends with this sequence (excluding the last),
                // then we set the logits of the last bad word id to -Infinity.
                for ($j = 1; $j <= count($badWordIds) - 1; $j++) {
                    if ($badWordIds[count($badWordIds) - $j - 1] != $ids[count($ids) - $j]) {
                        // We have found a mismatch
                        $mark = false;
                        break;
                    }
                }

                if ($mark) {
                    $batchLogits->buffer()[$badWordIds[count($badWordIds) - 1]] = -INF;
                }
            }
        }

        return $logits;
    }
}