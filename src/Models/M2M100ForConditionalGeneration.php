<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use OnnxRuntime\InferenceSession;

class M2M100ForConditionalGeneration extends M2M100Model
{
    protected mixed $numDecoderLayers;
    protected mixed $numDecoderHeads;
    protected mixed $decoderDimKv;
    protected mixed $numEncoderLayers;
    protected mixed $numEncoderHeads;
    protected mixed $encoderDimKv;

    public function __construct(
        AutoConfig              $config,
        InferenceSession        $session,
        public InferenceSession $decoderMergedSession,
        public ModelGroup       $modelGroup,
        public GenerationConfig $generationConfig
    )
    {
        parent::__construct($config, $session, $modelGroup);

        $this->numDecoderLayers = $this->config['decoder_layers'];
        $this->numDecoderHeads = $this->config['decoder_attention_heads'];
        $this->decoderDimKv = $this->config['d_model'] / $this->numDecoderHeads;

        $this->numEncoderLayers = $this->config['encoder_layers'];
        $this->numEncoderHeads = $this->config['encoder_attention_heads'];
        $this->encoderDimKv = $this->config['d_model'] / $this->numEncoderHeads;
    }

}