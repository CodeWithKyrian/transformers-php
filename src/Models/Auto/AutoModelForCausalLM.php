<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForCausalLM extends PretrainedMixin
{
    const MODEL_CLASS_MAPPING = [
        'gpt2' => \Codewithkyrian\Transformers\Models\Pretrained\GPT2LMHeadModel::class,
        'gptj' => \Codewithkyrian\Transformers\Models\Pretrained\GPTJForCausalLM::class,
        'gpt_bigcode' => \Codewithkyrian\Transformers\Models\Pretrained\GPTBigCodeForCausalLM::class,
        'codegen' => \Codewithkyrian\Transformers\Models\Pretrained\CodeGenForCausalLM::class,
        'qwen2' => \Codewithkyrian\Transformers\Models\Pretrained\Qwen2ForCausalLM::class
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}