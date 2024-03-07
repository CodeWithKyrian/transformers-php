<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\Samplers;

use Codewithkyrian\Transformers\Utils\Tensor;

class GreedySampler extends Sampler
{

    /**
     * Sample the maximum probability of a given logits tensor.
     *
     * @param Tensor $logits
     * @param int $index
     * @return array An array with a single tuple, containing the index of the maximum value and a meaningless score (since this is a greedy search).
     */
    public function sample(Tensor $logits, int $index): array
    {
        // NOTE: no need to do log_softmax here since we only take the maximum
        $logs = $this->getLogits($logits, $index);
        $argmax = array_search(max($logs), $logs);

        // Note: score is meaningless in this context, since we are performing
        // greedy search (p = 1 => log(p) = 0)
        return [
            [$argmax, 0]
        ];
    }
}