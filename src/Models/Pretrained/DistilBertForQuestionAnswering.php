<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\QuestionAnsweringModelOutput;

/**
 * DistilBertForQuestionAnswering is a class representing a DistilBERT model for question answering.
 */
class DistilBertForQuestionAnswering extends BertPreTrainedModel
{
    public function __invoke(array $modelInputs): QuestionAnsweringModelOutput
    {
        return QuestionAnsweringModelOutput::fromOutput(parent::__invoke($modelInputs));
    }
}