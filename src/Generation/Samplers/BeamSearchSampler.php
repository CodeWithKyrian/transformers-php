<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\Samplers;

use Codewithkyrian\Transformers\Utils\Math;
use Codewithkyrian\Transformers\Utils\Tensor;

class BeamSearchSampler extends Sampler
{

    /**
     * Sample from the logits.
     *
     * @param Tensor $logits
     * @param int $index
     * @return array
     */
    public function sample(Tensor $logits, int $index)
    {
        $shape = $logits->shape();
        $k = end($shape); // defaults to vocab size

        if ($this->generationConfig->top_k > 0) {
            $k = min($this->generationConfig->top_k, $k);
        }

        // Get logits of nth token
        $logs = $this->getLogits($logits, $index);

        // Get top k tokens
        $topLogits = Math::getTopItems($logs, $k);
        $topLogits = array_map(fn($x, $i) => [$i, $x], $topLogits, array_keys($topLogits));

        // Compute softmax over logits
        $probabilities = Math::softmax(array_column($topLogits, 1));

        $sampledResults = [];
        for ($i = 0; $i < $this->generationConfig->num_beams; $i++) {
            $sampledResults[] = [
                $topLogits[$i][0], // token id
                log($probabilities[$i]), // score
            ];
        }

        return $sampledResults;
    }
}