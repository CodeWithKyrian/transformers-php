<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\MaskedLMOutput;

/**
 * DebertaForMaskedLM class for performing masked language modeling on DeBERTa models.
 */
class DebertaForMaskedLM extends DebertaPretrainedModel
{
    public function __invoke(array $modelInputs): MaskedLMOutput
    {
        return MaskedLMOutput::fromOutput(parent::__invoke($modelInputs));
    }

}