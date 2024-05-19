<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForAudioClassification  extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'audio-spectrogram-transformer' => \Codewithkyrian\Transformers\Models\Pretrained\ASTForAudioClassification::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}