<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForCausalLM extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'gpt' => \Codewithkyrian\Transformers\Models\Pretrained\GPT2LMHeadModel::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}