<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForSeq2SeqLM extends AutoModelBase
{
    const MODELS = [
        'bart' => \Codewithkyrian\Transformers\Models\Pretrained\BartForConditionalGeneration::class,
        't5' => \Codewithkyrian\Transformers\Models\Pretrained\T5ForConditionalGeneration::class,
        'm2m_100' => \Codewithkyrian\Transformers\Models\Pretrained\M2M100ForConditionalGeneration::class,
    ];
}
