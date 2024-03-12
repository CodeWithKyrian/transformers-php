<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;

/**
 * RoFormer Model transformer with a sequence classification/regression head on top (a linear layer on top of the pooled output)
 */
class RoFormerForSequenceClassification extends RoFormerPreTrainedModel
{
    public function __invoke(array $modelInputs): SequenceClassifierOutput
    {
        return SequenceClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}