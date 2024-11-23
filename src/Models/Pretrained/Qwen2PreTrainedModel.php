<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Configs\PretrainedConfig;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\InferenceSession;

/**
 * The bare Qwen2 Model outputting raw hidden-states without any specific head on top.
 */
class Qwen2PreTrainedModel extends PreTrainedModel
{
}
