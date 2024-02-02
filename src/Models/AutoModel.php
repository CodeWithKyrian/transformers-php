<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Pipelines\Task;
use Codewithkyrian\Transformers\Utils\AutoConfig;

class AutoModel
{
    public static function fromPretrained(
        string      $modelNameOrPath,
        string|Task $task,
        bool        $quantized = true,
        ?array      $config = null,
        ?string     $cacheDir = null,
        ?string     $token = null,
        string      $revision = 'main',
        ?string     $modelFilename = null,
    ): PreTrainedModel
    {
        $config = AutoConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision);

        $modelGroup = $task->modelGroup();
        $model = $modelGroup->models()[$config->modelType]
            ?? throw new \Error("Model group {$modelGroup->value} does not contain a model for type {$config->modelType}.");

        return $model::fromPretrained(
            modelNameOrPath: $modelNameOrPath,
            quantized: $quantized,
            config: $config,
            cacheDir: $cacheDir,
            token: $token,
            revision: $revision,
            modelFilename: $modelFilename,
        );
    }
}