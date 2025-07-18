<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForTokenClassification extends AutoModelBase
{
    const MODELS = [
        "bert" => \Codewithkyrian\Transformers\Models\Pretrained\BertForTokenClassification::class,
        "deberta" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaForTokenClassification::class,
        "deberta-v2" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForTokenClassification::class,
        "roberta" => \Codewithkyrian\Transformers\Models\Pretrained\RobertaForTokenClassification::class,
        'roformer' => \Codewithkyrian\Transformers\Models\Pretrained\RoFormerForTokenClassification::class,
    ];
}
