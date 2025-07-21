<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForImageFeatureExtraction extends AutoModelBase
{
    const MODELS = [
        'clip' => \Codewithkyrian\Transformers\Models\Pretrained\CLIPVisionModelWithProjection::class,
        'siglip' => \Codewithkyrian\Transformers\Models\Pretrained\SiglipVisionModel::class,
    ];
}
