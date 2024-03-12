<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;

/**
 * DebertaForSequenceClassification is a class representing a DeBERTa model for sequence classification.
 */
class DebertaForSequenceClassification extends DebertaPreTrainedModel
{
    public function __invoke(array $modelInputs): SequenceClassifierOutput
    {
        return SequenceClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}