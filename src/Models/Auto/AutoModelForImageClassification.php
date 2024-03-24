<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForImageClassification extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'vit' => \Codewithkyrian\Transformers\Models\Pretrained\ViTForImageClassification::class
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}