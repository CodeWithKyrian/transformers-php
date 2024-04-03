<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\DetrSegmentationOutput;

class DetrForSegmentation extends DetrPretrainedModel
{
    public function __invoke(array $modelInputs): DetrSegmentationOutput
    {
        return DetrSegmentationOutput::fromOutput(parent::__invoke($modelInputs));
    }
}