<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

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

    public function __invoke(array $inputIds, NDArrayPhp|Tensor &$logits): Tensor|NDArrayPhp
    {
        if (count($inputIds) >= $this->maxLength) {
            foreach ($logits->buffer() as $i => $value) {
                $logits->buffer()[$i] = -INF;
            }
            $logits->buffer()[$this->forcedEosTokenId] = 0;
        }
        return $logits;
    }
}