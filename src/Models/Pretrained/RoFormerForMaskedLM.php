<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\MaskedLMOutput;

/**
 * RoFormer Model with a `language modeling` head on top.
 */
class RoFormerForMaskedLM extends RoFormerPretrainedModel
{
    public function __invoke(array $modelInputs): MaskedLMOutput
    {
        return MaskedLMOutput::fromOutput(parent::__invoke($modelInputs));
    }

}