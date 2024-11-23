<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Configs\PretrainedConfig;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\InferenceSession;

/**
 * The BART Model with a language modeling head. Can be used for summarization.
 */
class BartForConditionalGeneration extends BartPretrainedModel
{
    public function __construct(
        PretrainedConfig               $config,
        InferenceSession         $session,
        public InferenceSession  $decoderMergedSession,
        public ModelArchitecture $modelArchitecture,
        public GenerationConfig  $generationConfig
    )
    {
        parent::__construct($config, $session, $modelArchitecture);
    }
}
