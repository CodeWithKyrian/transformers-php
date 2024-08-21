<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\ModelOutput;
use Codewithkyrian\Transformers\Models\Output\TokenClassifierOutput;

/**
 * Wav2Vec2 Model with a frame classification head on top for tasks like Speaker Diarization.
 */
class Wav2Vec2ForAudioFrameClassification extends Wav2Vec2PretrainedModel
{
public function __invoke(array $modelInputs): array|ModelOutput
{
    return TokenClassifierOutput::fromOutput(parent::__invoke($modelInputs));
}
}