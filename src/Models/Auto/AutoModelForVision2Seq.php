<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForVision2Seq extends AutoModelBase
{
    const MODEL_CLASS_MAPPING = [
        'vision-encoder-decoder' => \Codewithkyrian\Transformers\Models\Pretrained\VisionEncoderDecoderModel::class
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
