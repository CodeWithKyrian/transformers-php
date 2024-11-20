<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\StoppingCriteria;

use Codewithkyrian\Transformers\Transformers;

/**
 * This class stops generation whenever the full generated number of tokens exceeds `maxLength`.
 * For decoder-only transformers, this includes the initial prompted tokens.
 */
class MaxLengthCriteria extends StoppingCriteria
{


    /**
     * @param int $maxLength The maximum length that the output sequence can have in number of tokens.
     * @param int|null $maxPositionEmbeddings The maximum model length,
     */
    public function __construct(protected int $maxLength, protected ?int $maxPositionEmbeddings = null) {}

    /**
     * Evaluates whether generation should stop based on token count.
     *
     * @param array $inputIds Array of input IDs (2D array where each sub-array is a sequence of token IDs).
     * @param array $scores Optional scores for the generated tokens.
     *
     * @return array|bool[]
     */
    public function __invoke(array $inputIds, array $scores): array
    {
//        return array_map(fn ($ids) => count($ids) >= $this->maxLength, $inputIds);
        $results = [];
        foreach ($inputIds as $ids) {
            $currentLength = count($ids);
            $isDone = $currentLength >= $this->maxLength;

            if ($this->maxPositionEmbeddings !== null && !$isDone && $currentLength >= $this->maxPositionEmbeddings) {
                echo
                    "This is a friendly reminder - the current text generation call will exceed the model's predefined " .
                    "maximum length ({$this->maxPositionEmbeddings}). Depending on the model, you may observe " .
                    "exceptions, performance degradation, or nothing at all."
                ;
            }

            $results[] = $isDone;
        }

        return $results;
    }
}
