<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForAudioClassification  extends AutoModelBase
{
    const MODELS = [
        'audio-spectrogram-transformer' => \Codewithkyrian\Transformers\Models\Pretrained\ASTForAudioClassification::class,
        'wav2vec2' => \Codewithkyrian\Transformers\Models\Pretrained\Wav2Vec2ForSequenceClassification::class,
    ];
}
