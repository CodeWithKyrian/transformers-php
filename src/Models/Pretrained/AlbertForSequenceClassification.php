<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;

/**
 * AlbertForSequenceClassification is a class representing a Albert model for sequence classification.
 */
class AlbertForSequenceClassification extends BertPreTrainedModel
{
    public function __invoke(array $modelInputs): SequenceClassifierOutput
    {
        return SequenceClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}