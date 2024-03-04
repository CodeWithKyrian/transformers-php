<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Pipelines\Task;
use Codewithkyrian\Transformers\Utils\AutoConfig;

class AutoModel
{
    public static function fromPretrained(
        string           $modelNameOrPath,
        string|Task|null $task = null,
        bool             $quantized = true,
        ?array           $config = null,
        ?string          $cacheDir = null,
        ?string          $token = null,
        string           $revision = 'main',
        ?string          $modelFilename = null,
    ): PreTrainedModel
    {
        $config = AutoConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision);

        if (is_string($task)) $task = Task::from($task);

        $modelGroup = $task?->modelGroup() ?? ModelGroup::inferFromModelType($config->modelType);

        return $modelGroup->constructModel(
            modelNameOrPath: $modelNameOrPath,
            quantized: $quantized,
            config: $config,
            cacheDir: $cacheDir,
            token: $token,
            revision: $revision,
            modelFilename: $modelFilename
        );
    }
}