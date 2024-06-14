<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\InferenceSession;

class GPT2PretrainedModel extends PretrainedModel
{
    public int $numHeads;
    public int $numLayers;
    public int $dimKv;

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

        $this->numHeads = $this->config['n_head'];
        $this->numLayers = $this->config['n_layer'];
        $this->dimKv = $this->config['n_embd'] / $this->numHeads;
    }
}