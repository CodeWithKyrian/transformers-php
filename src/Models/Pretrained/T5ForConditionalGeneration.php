<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Configs\GenerationConfig;
use Codewithkyrian\Transformers\Configs\PretrainedConfig;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\InferenceSession;

/**
 * T5Model is a class representing a T5 model for conditional generation.
 */
class T5ForConditionalGeneration extends T5PretrainedModel
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
