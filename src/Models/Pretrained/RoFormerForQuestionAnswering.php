<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\QuestionAnsweringModelOutput;

/**
 * RoFormer Model with a span classification head on top for extractive question-answering tasks like SQuAD
 * (a linear layers on top of the hidden-states output to compute `span start logits` and `span end logits`).
 */
class RoFormerForQuestionAnswering extends RobertaPreTrainedModel
{
    public function __invoke(array $modelInputs): QuestionAnsweringModelOutput
    {
        return QuestionAnsweringModelOutput::fromOutput(parent::__invoke($modelInputs));
    }
}