<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\MaskedLMOutput;

/**
 * DistilBertForMaskedLM class for performing masked language modeling on DistilBERT models.
 */
class DistilBertForMaskedLM extends RobertaPretrainedModel
{
    public function __invoke(array $modelInputs): MaskedLMOutput
    {
        return MaskedLMOutput::fromOutput(parent::__invoke($modelInputs));
    }

}