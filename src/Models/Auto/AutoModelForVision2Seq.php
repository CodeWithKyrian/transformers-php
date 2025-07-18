<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForVision2Seq extends AutoModelBase
{
    const MODELS = [
        'vision-encoder-decoder' => \Codewithkyrian\Transformers\Models\Pretrained\VisionEncoderDecoderModel::class
    ];
}
