<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;
use Codewithkyrian\Transformers\Models\Output\TokenClassifierOutput;

/**
 * RobertaForTokenClassification class for performing token classification on Roberta models.
 */
class RobertaForTokenClassification extends RobertaPreTrainedModel
{
    public function __invoke(array $modelInputs): TokenClassifierOutput
    {
        return TokenClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}