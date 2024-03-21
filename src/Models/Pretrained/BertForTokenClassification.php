<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\TokenClassifierOutput;

/**
 * BertForTokenClassification is a class representing a BERT model for token classification.
 */
class BertForTokenClassification extends BertPretrainedModel
{
    public function __invoke(array $modelInputs): TokenClassifierOutput
    {
        return TokenClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}