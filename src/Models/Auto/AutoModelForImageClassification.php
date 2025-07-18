<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForImageClassification extends AutoModelBase
{
    const MODELS = [
        'vit' => \Codewithkyrian\Transformers\Models\Pretrained\ViTForImageClassification::class,
        'deit' => \Codewithkyrian\Transformers\Models\Pretrained\DeiTForImageClassification::class,
    ];
}
