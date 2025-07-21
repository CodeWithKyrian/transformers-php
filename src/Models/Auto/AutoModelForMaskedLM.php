<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForMaskedLM extends AutoModelBase
{
    const MODELS = [
        "albert" => \Codewithkyrian\Transformers\Models\Pretrained\AlbertForMaskedLM::class,
        "bert" => \Codewithkyrian\Transformers\Models\Pretrained\BertForMaskedLM::class,
        "deberta" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaForMaskedLM::class,
        "deberta-v2" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForMaskedLM::class,
        "distilbert" => \Codewithkyrian\Transformers\Models\Pretrained\DistilBertForMaskedLM::class,
        "mobilebert" => \Codewithkyrian\Transformers\Models\Pretrained\MobileBertForMaskedLM::class,
        "roberta" => \Codewithkyrian\Transformers\Models\Pretrained\RobertaForMaskedLM::class,
        "roformer" => \Codewithkyrian\Transformers\Models\Pretrained\RoFormerForMaskedLM::class,
    ];
}
