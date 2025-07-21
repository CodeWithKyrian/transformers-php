<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\StoppingCriteria;

class MaxTimeCriteria extends StoppingCriteria
{
    private float $maxTime;
    private float $initialTimestamp;

    /**
     * @param float $maxTime The maximum allowed time in seconds for the generation.
     * @param float|null $initialTimestamp The start of the generation allowed time. Defaults to the current time.
     */
    public function __construct(float $maxTime, ?float $initialTimestamp = null)
    {
        $this->maxTime = $maxTime;
        $this->initialTimestamp = $initialTimestamp ?? microtime(true);
    }

    /**
     * Evaluates whether generation should stop based on elapsed time.
     *
     * @param array $inputIds Array of input IDs (2D array where each sub-array is a sequence of token IDs).
     * @param array $scores  Scores for the generated tokens.
     *
     * @return array Boolean array indicating whether generation should stop for each sequence.
     */
    public function __invoke(array $inputIds, array $scores): array
    {
        $elapsedTime = microtime(true) - $this->initialTimestamp;
        $isDone = $elapsedTime > $this->maxTime;

        // Return the same stopping criteria for all sequences
        return array_fill(0, count($inputIds), $isDone);
    }
}
