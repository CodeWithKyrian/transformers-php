<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use OnnxRuntime\InferenceSession;

class TrOCRPretrainedModel extends PretrainedModel
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
        public ModelArchitecture $modelArchitecture,
        public GenerationConfig  $generationConfig
    )
    {
        parent::__construct($config, $session, $modelArchitecture);


        $this->numEncoderLayers =  $this->numDecoderLayers = $this->config['decoder_layers'];
        $this->numEncoderHeads =  $this->numDecoderHeads = $this->config['decoder_attention_heads'];
        $this->encoderDimKv =  $this->decoderDimKv = $this->config['d_model'] / $this->numDecoderHeads;
    }
}