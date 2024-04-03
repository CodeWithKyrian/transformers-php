<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForObjectDetection extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'detr' => \Codewithkyrian\Transformers\Models\Pretrained\DetrForObjectDetection::class,
        'yolos' => \Codewithkyrian\Transformers\Models\Pretrained\YolosForObjectDetection::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];

}