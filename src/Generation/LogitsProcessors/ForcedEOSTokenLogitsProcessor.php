<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

/**
 * A logits processor that forces end-of-sequence token probability to 1.
 */
class ForcedEOSTokenLogitsProcessor extends LogitsProcessor
{
    public function __construct(
        protected int $maxLength,
        protected int $forcedEosTokenId
    )
    {
    }

    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        if (count($inputIds) >= $this->maxLength) {
            Tensor::mo()->la()->fill(-INF, $logits);
            $logits->buffer()[$this->forcedEosTokenId] = 0;
        }
        return $logits;
    }
}