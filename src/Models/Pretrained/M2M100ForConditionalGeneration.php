<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Configs\GenerationConfig;
use Codewithkyrian\Transformers\Configs\PretrainedConfig;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\InferenceSession;

class M2M100ForConditionalGeneration extends M2M100PretrainedModel
{
    public function __construct(
        PretrainedConfig         $config,
        InferenceSession         $session,
        public InferenceSession  $decoderMergedSession,
        public ModelArchitecture $modelArchitecture,
        public GenerationConfig  $generationConfig
    ) {
        parent::__construct($config, $session, $modelArchitecture);
    }
}
