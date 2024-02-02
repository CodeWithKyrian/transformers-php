<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers;

use Codewithkyrian\Transformers\Models\AutoModel;
use Codewithkyrian\Transformers\Models\BertModel;
use Codewithkyrian\Transformers\Pipelines\Pipeline;
use Codewithkyrian\Transformers\Pipelines\Task;
use Codewithkyrian\Transformers\PretrainedTokenizers\AutoTokenizer;

/**
 * Utility factory method to build a `Pipeline` object.
 * @param string|Task $task The task defining which pipeline will be returned. Currently accepted tasks are:
 * - "feature-extraction": will return a `FeatureExtractionPipeline`.
 * - "sentiment-analysis": will return a `TextClassificationPipeline`.
 * - "ner": will return a `TokenClassificationPipeline`.
 * - "question-answering": will return a `QuestionAnsweringPipeline`.
 * - "fill-mask": will return a `FillMaskPipeline`.
 * - "summarization": will return a `SummarizationPipeline`.
 * - "translation_xx_to_yy": will return a `TranslationPipeline`.
 * - "text-generation": will return a `TextGenerationPipeline`.
 * @param string|null $model The name of the pre-trained model to use. If not specified, the default model for the task will be used.
 * @param bool $quantized Whether to use a quantized version of the model. If the model doesn't have a quantized version, will
 * default to `false`. Only available for some models.
 * @param array|null $config The configuration to use for the pipeline.
 * @param string|null $cacheDir The directory in which the pre-trained models will be cached. Will default to the Transformers cache directory
 * @param string|null $token The secret API token to use for the model's inference API.
 * @param string $revision The specific model version to use. It can be a branch name, a tag name, or a commit id, since we use a git-based
 * system for storing models and other artifacts on huggingface.co, so ``revision`` can be any identifier allowed by git.
 * @return Pipeline
 */
function pipeline(
    string|Task $task,
    ?string     $modelName = null,
    bool        $quantized = true,
    ?array      $config = null,
    ?string     $cacheDir = null,
    ?string     $token = null,
    string      $revision = 'main',
) : Pipeline
{
    if (is_string($task)) {
        $task = Task::from($task);
    }

    $modelName ??= $task->defaultModel();

    $model = AutoModel::fromPretrained($modelName, $task, $quantized, $config, $cacheDir, $token, $revision);

    $tokenizer = AutoTokenizer::fromPretrained($modelName, $quantized, $config, $cacheDir, $token, $revision);

    $pipelineClass = $task->pipeline();

    return new $pipelineClass($task, $model, $tokenizer);
}
