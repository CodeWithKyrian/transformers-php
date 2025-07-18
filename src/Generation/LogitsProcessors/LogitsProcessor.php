<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

abstract class LogitsProcessor
{
    /**
     * Abstract method to apply the processor to input logits.
     *
     * @param Tensor[] $inputIds The input IDs.
     * @param Tensor $logits The logits to process.
     * @return Tensor The processed logits.
     */
    abstract public function __invoke(array $inputIds, Tensor $logits): Tensor;
}
