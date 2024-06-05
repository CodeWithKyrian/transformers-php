<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\CasualLMOutput;
use Codewithkyrian\Transformers\Models\Output\ModelOutput;

class Wav2Vec2ForCTC extends Wav2Vec2PretrainedModel
{
//    public function __invoke(array $modelInputs): array|ModelOutput
//    {
//        return CasualLMOutput::fromOutput(parent::__invoke($modelInputs));
//    }
}