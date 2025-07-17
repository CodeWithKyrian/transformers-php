<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Configs;

/**
 * Helper class which is used to instantiate pretrained configs with the `fromPretrained` function.
 */
class AutoConfig
{

    public static function fromPretrained(
        string    $modelNameOrPath,
        ?array    $config = null,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        ?callable $onProgress = null
    ): PretrainedConfig {
        return PretrainedConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision, $onProgress);
    }
}
