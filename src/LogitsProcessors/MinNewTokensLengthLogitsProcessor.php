<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\LogitsProcessors;

use Codewithkyrian\Transformers\LogitsProcessors\LogitsProcessor;
use Codewithkyrian\Transformers\Utils\Tensor;

class MinNewTokensLengthLogitsProcessor extends LogitsProcessor
{

    /**
     * @inheritDoc
     */
    public function __invoke(array $inputIds, Tensor &$logits): Tensor
    {
        // TODO: Implement __invoke() method.
    }
}