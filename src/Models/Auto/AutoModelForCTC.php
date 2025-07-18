<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForCTC extends AutoModelBase
{
    const MODEL_CLASS_MAPPING = [
        'wav2vec2' => \Codewithkyrian\Transformers\Models\Pretrained\Wav2Vec2ForCTC::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
