<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModelForCausalLM extends AutoModelBase
{
    const MODELS = [
        'gpt2' => \Codewithkyrian\Transformers\Models\Pretrained\GPT2LMHeadModel::class,
        'gptj' => \Codewithkyrian\Transformers\Models\Pretrained\GPTJForCausalLM::class,
        'gpt_bigcode' => \Codewithkyrian\Transformers\Models\Pretrained\GPTBigCodeForCausalLM::class,
        'codegen' => \Codewithkyrian\Transformers\Models\Pretrained\CodeGenForCausalLM::class,
        'llama' => \Codewithkyrian\Transformers\Models\Pretrained\LlamaForCausalLM::class,
        'trocr' => \Codewithkyrian\Transformers\Models\Pretrained\TrOCRForCausalLM::class,
        'qwen2' => \Codewithkyrian\Transformers\Models\Pretrained\Qwen2ForCausalLM::class,
        'gemma' => \Codewithkyrian\Transformers\Models\Pretrained\GemmaForCausalLM::class,
        'gemma2' => \Codewithkyrian\Transformers\Models\Pretrained\Gemma2ForCausalLM::class,
        'gemma3' => \Codewithkyrian\Transformers\Models\Pretrained\Gemma3ForCausalLM::class,
        'qwen3' => \Codewithkyrian\Transformers\Models\Pretrained\Qwen3ForCausalLM::class,
        'phi' => \Codewithkyrian\Transformers\Models\Pretrained\PhiForCausalLM::class,
        'phi3' => \Codewithkyrian\Transformers\Models\Pretrained\Phi3ForCausalLM::class,
    ];
}
