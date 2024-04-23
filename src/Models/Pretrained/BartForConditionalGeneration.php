<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\InferenceSession;

/**
 * The BART Model with a language modeling head. Can be used for summarization.
 */
class BartForConditionalGeneration extends BartPretrainedModel
{
    public mixed $numDecoderLayers;
    public mixed $numDecoderHeads;
    public mixed $decoderDimKv;
    public mixed $numEncoderLayers;
    public mixed $numEncoderHeads;
    public mixed $encoderDimKv;

    public function __construct(
        AutoConfig               $config,
        InferenceSession         $session,
        public InferenceSession  $decoderMergedSession,
        public ModelArchitecture $modelArchitecture,
        public GenerationConfig  $generationConfig
    )
    {
        parent::__construct($config, $session, $modelArchitecture);

        $this->numDecoderLayers = $this->config['decoder_layers'];
        $this->numDecoderHeads = $this->config['decoder_attention_heads'];
        $this->decoderDimKv = $this->config['d_model'] / $this->numDecoderHeads;

        $this->numEncoderLayers = $this->config['encoder_layers'];
        $this->numEncoderHeads = $this->config['encoder_attention_heads'];
        $this->encoderDimKv = $this->config['d_model'] / $this->numEncoderHeads;
    }
}