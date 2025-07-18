<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForAudioClassification  extends AutoModelBase
{
    const MODEL_CLASS_MAPPING = [
        'audio-spectrogram-transformer' => \Codewithkyrian\Transformers\Models\Pretrained\ASTForAudioClassification::class,
        'wav2vec2' => \Codewithkyrian\Transformers\Models\Pretrained\Wav2Vec2ForSequenceClassification::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
