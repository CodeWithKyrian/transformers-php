<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForObjectDetection extends AutoModelBase
{
    const MODELS = [
        'detr' => \Codewithkyrian\Transformers\Models\Pretrained\DetrForObjectDetection::class,
        'yolos' => \Codewithkyrian\Transformers\Models\Pretrained\YolosForObjectDetection::class,
    ];
}
