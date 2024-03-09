<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\ModelOutput;
use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;

/**
 * Bart model with a sequence classification/head on top (a linear layer on top of the pooled output)
 */
class BartForSequenceClassification extends BartPretrainedModel
{
    public function __invoke(array $modelInputs): SequenceClassifierOutput
    {
        return SequenceClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}