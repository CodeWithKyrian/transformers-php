<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Output;

use Codewithkyrian\Transformers\Tensor\Tensor;

class CasualLMOutput implements ModelOutput
{
    public function __construct(public readonly Tensor $logits)
    {
    }

    public static function fromOutput(array $array): self
    {
        return new self($array['logits']);
    }
}