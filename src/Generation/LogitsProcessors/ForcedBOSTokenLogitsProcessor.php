<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

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
        if (count($inputIds) === 1) {
            Tensor::mo()->la()->fill(-INF, $logits);
            $logits->buffer()[$this->bosTokenId] = 0;
        }
        return $logits;
    }
}