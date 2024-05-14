<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\OnnxRuntime\InferenceSession;
use Codewithkyrian\Transformers\Utils\AutoConfig;

class CodeGenPretrainedModel extends PretrainedModel
{
    protected int $numHeads;
    protected int $numLayers;
    protected int $dimKv;

    public function __construct(
        AutoConfig        $config,
        InferenceSession  $session,
        ModelArchitecture $modelArchitecture = ModelArchitecture::EncoderOnly,
                          ...$args
    )
    {
        parent::__construct($config, $session, $modelArchitecture, $args);

        // config doesn't contain pad_token_id, so we assume it is the eos_token_id
        $this->config['pad_token_id'] = $this->config['eos_token_id'];
        $this->config->padTokenId = $this->config['eos_token_id'];

        $this->numHeads = $this->config['n_head'];
        $this->numLayers = $this->config['n_layer'];
        $this->dimKv = $this->config['n_embd'] / $this->numHeads;

    }
}