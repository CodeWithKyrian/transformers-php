<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\MaskedLMOutput;

/**
 * DeBERTa-V2 Model with a `language modeling` head on top.
 */
class DebertaV2ForMaskedLM extends DebertaV2PreTrainedModel
{
    public function __invoke(array $modelInputs): MaskedLMOutput
    {
        return MaskedLMOutput::fromOutput(parent::__invoke($modelInputs));
    }

}