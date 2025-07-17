<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Configs\PretrainedConfig;
use Codewithkyrian\Transformers\Models\ModelArchitecture;

/**
 * CLIP Vision Model with a projection layer on top (a linear layer on top of the pooled output)
 *
 * Particularly useful for image feature extraction tasks.
 */
class CLIPVisionModelWithProjection extends CLIPPretrainedModel
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
