<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;


class AutoModelForSequenceClassification extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'bert' => \Codewithkyrian\Transformers\Models\Pretrained\BertForSequenceClassification::class,
        'bart' => \Codewithkyrian\Transformers\Models\Pretrained\BartForSequenceClassification::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}