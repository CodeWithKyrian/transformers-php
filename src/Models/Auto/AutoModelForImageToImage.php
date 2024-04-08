<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForImageToImage extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'swin2sr' => \Codewithkyrian\Transformers\Models\Pretrained\Swin2SRForImageSuperResolution::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}