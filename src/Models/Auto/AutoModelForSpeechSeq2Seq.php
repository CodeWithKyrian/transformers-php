<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForSpeechSeq2Seq extends AutoModelBase
{
    const MODELS = [
        "whisper" => \Codewithkyrian\Transformers\Models\Pretrained\WhisperForConditionalGeneration::class,
    ];
}
