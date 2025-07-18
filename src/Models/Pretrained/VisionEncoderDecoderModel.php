<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

/**
 * Vision Encoder-Decoder model based on OpenAI's GPT architecture for image captioning and other vision tasks
 */
class VisionEncoderDecoderModel extends PretrainedModel
{
    public string $mainInputName = 'pixel_values';
    protected array $forwardParams = [
        // Encoder inputs
        'pixel_values',

        // Decoder inputs
        'decoder_input_ids',
        'encoder_hidden_states',
        'past_key_values',
    ];
}
