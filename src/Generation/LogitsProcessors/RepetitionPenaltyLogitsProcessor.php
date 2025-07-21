<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

/**
 * This processor penalizes the repetition of tokens in the generated text.
 */
class RepetitionPenaltyLogitsProcessor extends LogitsProcessor
{
    public function __construct(protected float $penalty) {}

    /**
     * Apply the repetition penalty to the logits.
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        // Modify the logits corresponding to each element in `input_ids`.
        // As a consequence, the logits corresponding to tokens that appear
        // many times in the output will be penalised more.
        for ($i = 0; $i < count($inputIds); $i++) {
            foreach ($inputIds[$i] as $inputId) {
                if ($logits[$i]->buffer()[$inputId] < 0) {
                    $logits[$i]->buffer()[$inputId] *= $this->penalty;
                } else {
                    $logits[$i]->buffer()[$inputId] /= $this->penalty;
                }
            }
        }

        return $logits;
    }
}
