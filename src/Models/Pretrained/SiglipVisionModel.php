<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Configs\PretrainedConfig;

class SiglipVisionModel extends CLIPPretrainedModel
{
    public static function fromPretrained(
        string $modelNameOrPath,
        bool $quantized = true,
        array|PretrainedConfig|null $config = null,
        ?string $cacheDir = null,
        ?string $token = null,
        string $revision = 'main',
        ?string $modelFilename = null,
        ModelArchitecture $modelArchitecture = ModelArchitecture::EncoderOnly,
        ?callable $onProgress = null
    ): PretrainedModel {
        $modelFilename ??= 'vision_model';
        return parent::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision, $modelFilename, $modelArchitecture, $onProgress);
    }
}
