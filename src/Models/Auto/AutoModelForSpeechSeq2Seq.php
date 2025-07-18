<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForSpeechSeq2Seq extends AutoModelBase
{
    const MODEL_CLASS_MAPPING = [
        "whisper" => \Codewithkyrian\Transformers\Models\Pretrained\WhisperForConditionalGeneration::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
