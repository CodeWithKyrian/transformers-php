<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\QuestionAnsweringModelOutput;

/**
 * RobertaForQuestionAnswering class for performing question answering on Roberta models.
 */
class RobertaForQuestionAnswering extends RobertaPretrainedModel
{
    public function __invoke(array $modelInputs): QuestionAnsweringModelOutput
    {
        return QuestionAnsweringModelOutput::fromOutput(parent::__invoke($modelInputs));
    }
}