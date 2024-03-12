<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

class BadWordsLogitsProcessor extends LogitsProcessor
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
    public function __invoke(array $inputIds, Tensor|NDArrayPhp &$logits): Tensor|NDArrayPhp
    {
        foreach ($this->badWordsIds as $badWordIds) {
            // Whether to modify the logits of the last token in the bad word id sequence
            $mark = true;

            // For each bad word in the list, if the current sequence of input ids ends with this sequence (excluding the last),
            // then we set the logits of the last bad word id to -Infinity.
            for ($i = 1; $i <= count($badWordIds) - 1 && count($badWordIds) < count($inputIds) + 1; ++$i) {

                if ($badWordIds[count($badWordIds) - $i - 1] !== array_slice($inputIds, -$i, 1)[0]) {
                    $mark = false;
                    break;
                }
            }
            if ($mark) {
                $lastBadWordIdIndex = array_pop($badWordIds);
                $logits['data'][$lastBadWordIdIndex] = -INF;
            }
        }

        return $logits;
    }
}