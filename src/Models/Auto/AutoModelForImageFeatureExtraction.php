<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForImageFeatureExtraction extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'clip' => \Codewithkyrian\Transformers\Models\Pretrained\CLIPVisionModelWithProjection::class,
        'siglip' => \Codewithkyrian\Transformers\Models\Pretrained\SiglipVisionModel::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
        AutoModel::ENCODER_ONLY_MODEL_MAPPING,
        AutoModel::DECODER_ONLY_MODEL_MAPPING,
    ];
}