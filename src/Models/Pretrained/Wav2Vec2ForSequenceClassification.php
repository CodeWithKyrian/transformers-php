<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\ModelOutput;
use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;

class Wav2Vec2ForSequenceClassification extends Wav2Vec2PretrainedModel
{
    public function __invoke(array $modelInputs): array|ModelOutput
    {
        return SequenceClassifierOutput::fromOutput(parent::__invoke($modelInputs));
    }
}