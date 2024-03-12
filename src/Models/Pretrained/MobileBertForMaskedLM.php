<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\MaskedLMOutput;

/**
 * MobileBertForMaskedLM is a class representing a MobileBERT model for masking task.
 */
class MobileBertForMaskedLM extends MobileBertPreTrainedModel
{
    public function __invoke(array $modelInputs): MaskedLMOutput
    {
        return MaskedLMOutput::fromOutput(parent::__invoke($modelInputs));
    }

}