<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\OnnxRuntime\InferenceSession;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;

/**
 * T5Model is a class representing a T5 model for conditional generation.
 */
class T5ForConditionalGeneration extends T5PretrainedModel
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

        $this->numDecoderLayers = $this->config['num_decoder_layers'];
        $this->numDecoderHeads = $this->config['num_heads'];
        $this->decoderDimKv = $this->config['d_kv'];

        $this->numEncoderLayers = $this->config['num_layers'];
        $this->numEncoderHeads = $this->config['num_heads'];
        $this->encoderDimKv = $this->config['d_kv'];
    }
}