<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForQuestionAnswering extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'albert' => \Codewithkyrian\Transformers\Models\Pretrained\AlbertForQuestionAnswering::class,
        'bert' => \Codewithkyrian\Transformers\Models\Pretrained\BertForQuestionAnswering::class,
        'deberta' => \Codewithkyrian\Transformers\Models\Pretrained\DebertaForQuestionAnswering::class,
        'deberta-v2' => \Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForQuestionAnswering::class,
        'distilbert' => \Codewithkyrian\Transformers\Models\Pretrained\DistilBertForQuestionAnswering::class,
        'mobilebert' => \Codewithkyrian\Transformers\Models\Pretrained\MobileBertForQuestionAnswering::class,
        'roberta' => \Codewithkyrian\Transformers\Models\Pretrained\RobertaForQuestionAnswering::class,
        'roformer' => \Codewithkyrian\Transformers\Models\Pretrained\RoFormerForQuestionAnswering::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}