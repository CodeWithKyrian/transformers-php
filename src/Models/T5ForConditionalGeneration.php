<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use OnnxRuntime\InferenceSession;

class T5ForConditionalGeneration extends T5Model
{
    protected mixed $numDecoderLayers;
    protected mixed $numDecoderHeads;
    protected mixed $decoderDimKv;
    protected mixed $numEncoderLayers;
    protected mixed $numEncoderHeads;
    protected mixed $encoderDimKv;

    public function __construct(
        AutoConfig                 $config,
        InferenceSession           $session,
        public InferenceSession $decoderMergedSessions,
        ModelGroup                 $modelGroup,
        public GenerationConfig    $generationConfig
    )
    {
        parent::__construct($config, $session);

        $this->numDecoderLayers = $this->config['num_decoder_layers'];
        $this->numDecoderHeads = $this->config['num_heads'];
        $this->decoderDimKv = $this->config['d_kv'];

        $this->numEncoderLayers = $this->config['num_layers'];
        $this->numEncoderHeads = $this->config['num_heads'];
        $this->encoderDimKv = $this->config['d_kv'];
    }
}