<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForTokenClassification extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        "bert" => \Codewithkyrian\Transformers\Models\Pretrained\BertForTokenClassification::class,
        "deberta" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaForTokenClassification::class,
        "deberta-v2" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForTokenClassification::class,
        "roberta" => \Codewithkyrian\Transformers\Models\Pretrained\RobertaForTokenClassification::class,
        'roformer' => \Codewithkyrian\Transformers\Models\Pretrained\RoFormerForTokenClassification::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}