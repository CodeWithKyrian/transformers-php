<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use OnnxRuntime\InferenceSession;

/**
 * T5Model is a class representing a T5 model for conditional generation.
 */
class T5ForConditionalGeneration extends T5PreTrainedModel
{
    protected mixed $numDecoderLayers;
    protected mixed $numDecoderHeads;
    protected mixed $decoderDimKv;
    protected mixed $numEncoderLayers;
    protected mixed $numEncoderHeads;
    protected mixed $encoderDimKv;

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