<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

class T5PretrainedModel extends PretrainedModel
{
    protected array $forwardParams = [
        'input_ids',
        'attention_mask',
        'encoder_outputs',
        'decoder_input_ids',
        'decoder_attention_mask',
        'past_key_values',
    ];
}
