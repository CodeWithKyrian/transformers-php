<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

class WhisperPretrainedModel extends PretrainedModel
{
    public string $mainInputName = 'input_features';
    protected array $forwardParams = [
        'input_features',
        'attention_mask',
        'decoder_input_ids',
        'decoder_attention_mask',
        'past_key_values',
    ];
}
