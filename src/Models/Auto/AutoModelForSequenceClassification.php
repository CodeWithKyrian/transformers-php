<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;


class AutoModelForSequenceClassification extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'albert' => \Codewithkyrian\Transformers\Models\Pretrained\AlbertForSequenceClassification::class,
        'bert' => \Codewithkyrian\Transformers\Models\Pretrained\BertForSequenceClassification::class,
        'bart' => \Codewithkyrian\Transformers\Models\Pretrained\BartForSequenceClassification::class,
        'deberta' => \Codewithkyrian\Transformers\Models\Pretrained\DebertaForSequenceClassification::class,
        'deberta-v2' => \Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForSequenceClassification::class,
        'distilbert' => \Codewithkyrian\Transformers\Models\Pretrained\DistilBertForSequenceClassification::class,
        'mobilebert' => \Codewithkyrian\Transformers\Models\Pretrained\MobileBertForSequenceClassification::class,
        'roberta' => \Codewithkyrian\Transformers\Models\Pretrained\RobertaForSequenceClassification::class,
        'roformer' => \Codewithkyrian\Transformers\Models\Pretrained\RoFormerForSequenceClassification::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}