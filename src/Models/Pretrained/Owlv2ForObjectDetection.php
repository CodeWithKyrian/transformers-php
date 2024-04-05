<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\ObjectDetectionOutput;

class Owlv2ForObjectDetection extends Owlv2PretrainedModel
{
    public function __invoke(array $modelInputs): ObjectDetectionOutput
    {
        return ObjectDetectionOutput::fromOutput(parent::__invoke($modelInputs));
    }
}