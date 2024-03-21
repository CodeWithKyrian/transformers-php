<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\TokenClassifierOutput;

/**
 * DebertaForTokenClassification is a class representing a DeBERTa model for token classification.
 */
class DebertaForTokenClassification extends DebertaPretrainedModel
{
    public function __invoke(array $modelInputs): TokenClassifierOutput
    {
        return TokenClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}