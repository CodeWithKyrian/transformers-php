<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

/**
 * This processor penalizes the repetition of tokens in the generated text.
 */
class RepetitionPenaltyLogitsProcessor extends LogitsProcessor
{
    public function __construct(protected float $penalty)
    {
    }

    /**
     * Apply the repetition penalty to the logits.
     */
    public function __invoke(array $inputIds, Tensor|NDArrayPhp &$logits): Tensor|NDArrayPhp
    {
        // Modify the logits corresponding to each element in `input_ids`.
        // As a consequence, the logits corresponding to tokens that appear
        // many times in the output will be penalised more.
        foreach ($inputIds as $inputId) {
            if ($logits->buffer()[$inputId] < 0) {
                $logits->buffer()[$inputId] *= $this->penalty;
            } else {
                $logits->buffer()[$inputId] /= $this->penalty;
            }
        }
        return $logits;
    }
}