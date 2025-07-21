<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\StoppingCriteria;

use Traversable;

/**
 * Abstract base class for all stopping criteria that can be applied during generation.
 */
abstract class StoppingCriteria
{
    /**
     * @param int[][] $inputIds Indices of input sequence tokens in the vocabulary of shape `(batch_size, sequence_length)`.
     * @param float[][] $scores Prediction scores of a language modeling head of shape `(batch_size, vocab_size)`.
     *
     * @return bool[] A list of booleans indicating whether each sequence should be stopped.
     */
    abstract public function __invoke(array $inputIds, array $scores): array;
}
