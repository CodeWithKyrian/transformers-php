<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForZeroShotObjectDetection extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'owlvit' => \Codewithkyrian\Transformers\Models\Pretrained\OwlViTForObjectDetection::class,
        'owlv2' => \Codewithkyrian\Transformers\Models\Pretrained\Owlv2ForObjectDetection::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];

}