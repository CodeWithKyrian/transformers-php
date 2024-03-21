<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;

/**
 * DeBERTa-V2 Model transformer with a sequence classification/regression head on top (a linear layer on top of the pooled output)
 */
class DebertaV2ForSequenceClassification extends DebertaV2PretrainedModel
{
    public function __invoke(array $modelInputs): SequenceClassifierOutput
    {
        return SequenceClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}