<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Configs\PretrainedConfig;

/**
 * The text model from SigLIP without any head or projection on top.
 */
class SiglipTextModel extends SiglipPretrainedModel
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
        $modelFilename ??= 'text_model';
        return parent::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision, $modelFilename, $modelArchitecture, $onProgress);
    }
}
