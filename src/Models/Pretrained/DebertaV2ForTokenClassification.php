<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\TokenClassifierOutput;

/**
 * DeBERTa-V2 Model with a token classification head on top (a linear layer on top of the hidden-states output) e.g. for Named-Entity-Recognition (NER) tasks.
 */
class DebertaV2ForTokenClassification extends DebertaV2PretrainedModel
{
    public function __invoke(array $modelInputs): TokenClassifierOutput
    {
        return TokenClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}