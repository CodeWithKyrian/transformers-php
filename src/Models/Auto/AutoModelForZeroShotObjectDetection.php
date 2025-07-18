<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForZeroShotObjectDetection extends AutoModelBase
{
    const MODELS = [
        'owlvit' => \Codewithkyrian\Transformers\Models\Pretrained\OwlViTForObjectDetection::class,
        'owlv2' => \Codewithkyrian\Transformers\Models\Pretrained\Owlv2ForObjectDetection::class,
    ];
}
