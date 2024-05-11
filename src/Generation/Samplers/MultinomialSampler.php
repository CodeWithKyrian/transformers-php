<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\Samplers;

use Codewithkyrian\Transformers\Tensor\Tensor;

class MultinomialSampler extends Sampler
{
    /**
     * Sample from the logits.
     *
     * @param Tensor $logits
     * @param int $index
     * @return array
     */
    public function sample(Tensor $logits, int $index): array
    {
        $vocabSize = $logits->shape()[$logits->ndim() - 1];

        $k = $this->generationConfig->top_k > 0
            ? min($this->generationConfig->top_k, $vocabSize)
            : $vocabSize; // defaults to vocab size

        // Get logits of nth token
        $logs = $this->getLogits($logits, $index);

        // Get top k tokens
        [$topLogits, $topIndices] = $logs->topk($k);

        // Compute softmax over logits
        $probabilities = $topLogits->softmax()->toArray();

        $sampledResults = [];

        for ($i = 0; $i < $this->generationConfig->num_beams; $i++) {
            $sampledIndex = $this->randomSelect($probabilities);

            $sampledResults[] = [
                $topIndices[$sampledIndex], // token id
                log($probabilities[$sampledIndex]), // score
            ];
        }

        return $sampledResults;
    }
}