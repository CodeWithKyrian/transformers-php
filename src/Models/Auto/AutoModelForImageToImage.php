<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForImageToImage extends AutoModelBase
{
    const MODELS = [
        'swin2sr' => \Codewithkyrian\Transformers\Models\Pretrained\Swin2SRForImageSuperResolution::class,
    ];
}
