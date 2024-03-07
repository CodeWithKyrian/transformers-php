<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;

class SuppressTokensAtBeginLogitsProcessor extends LogitsProcessor
{

    /**
     * @inheritDoc
     */
    public function __invoke(array $inputIds, Tensor &$logits): Tensor
    {
        // TODO: Implement __invoke() method.
    }
}