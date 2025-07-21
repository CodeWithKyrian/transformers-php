<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

/**
 * A LogitsProcessor that forces a BOS token at the beginning of the generated sequence.
 */
class ForcedBOSTokenLogitsProcessor extends LogitsProcessor
{

    public function __construct(
        protected int $bosTokenId
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        for ($i = 0; $i < count($inputIds); $i++) {
            if (count($inputIds[$i]) === 1) {
                $batchLogits = $logits[$i];
                Tensor::mo()->la()->fill(-INF, $batchLogits);
                $batchLogits->buffer()[$this->bosTokenId] = 0;
            }
        }
        return $logits;
    }
}