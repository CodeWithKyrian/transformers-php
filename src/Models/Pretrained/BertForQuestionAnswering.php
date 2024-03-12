<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\QuestionAnsweringModelOutput;

/**
 * BertForQuestionAnswering is a class representing a BERT model for question answering.
 */
class BertForQuestionAnswering extends BertPreTrainedModel
{
    public function __invoke(array $modelInputs): QuestionAnsweringModelOutput
    {
        return QuestionAnsweringModelOutput::fromOutput(parent::__invoke($modelInputs));
    }
}