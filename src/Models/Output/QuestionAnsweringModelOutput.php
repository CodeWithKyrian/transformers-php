<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Output;

use Codewithkyrian\Transformers\Tensor\Tensor;

/**
 * Base class for outputs of question answering models.
 */
class QuestionAnsweringModelOutput implements ModelOutput
{
    public function __construct(
        public readonly Tensor $startLogits,
        public readonly Tensor $endLogits,
    )
    {
    }

    public static function fromOutput(array $array): self
    {
        return new self($array['start_logits'], $array['end_logits']);
    }
}