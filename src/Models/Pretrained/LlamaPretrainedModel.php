<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\InferenceSession;


/**
 * The bare LLama Model outputting raw hidden-states without any specific head on top.
 */
class LlamaPretrainedModel extends PretrainedModel
{
    protected int $numHeads;
    protected int $numLayers;
    protected int $dimKv;

    public function __construct(
        AutoConfig               $config,
        InferenceSession         $session,
        public ModelArchitecture $modelArchitecture,
        public GenerationConfig  $generationConfig
    )
    {
        parent::__construct($config, $session, $modelArchitecture);

        // config doesn't contain pad_token_id, so we assume it is the eos_token_id
        $this->config['pad_token_id'] = $this->config['eos_token_id'];
        $this->config->padTokenId = $this->config['eos_token_id'];

        $this->numHeads = $this->config['num_key_value_heads'] ?? $this->config['num_attention_heads'];
        $this->numLayers = $this->config['num_hidden_layers'];
        $this->dimKv = $this->config['hidden_size'] / $this->config['num_attention_heads'];
    }
}