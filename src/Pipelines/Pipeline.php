<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Exceptions\UnsupportedTaskException;
use Codewithkyrian\Transformers\Models\Pretrained\PretrainedModel;
use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;
use Codewithkyrian\Transformers\Processors\Processor;
use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\Tensor;
use Symfony\Component\Console\Output\OutputInterface;

class Pipeline
{
    public function __construct(
        protected string|Task       $task,
        protected PretrainedModel   $model,
        public ?PretrainedTokenizer $tokenizer = null,
        protected ?Processor        $processor = null,
    )
    {
    }

    /**
     * @param string[]|string $inputs
     * @param ...$args
     * @return array
     */
    public function __invoke(array|string $inputs, ...$args): array|Tensor|Image
    {
        return [];
    }

}

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
 * @param string|null $modelName
 * @param bool $quantized Whether to use a quantized version of the model. If the model doesn't have a quantized version, will
 * default to `false`. Only available for some models.
 * @param array|null $config The configuration to use for the pipeline.
 * @param string|null $cacheDir The directory in which the pre-trained models will be cached. Will default to the Transformers cache directory
 * @param string $revision The specific model version to use. It can be a branch name, a tag name, or a commit id, since we use a git-based
 * system for storing models and other artifacts on huggingface.co, so ``revision`` can be any identifier allowed by git.
 * @return Pipeline
 * @throws UnsupportedTaskException If the task is not supported.
 */
function pipeline(
    string|Task      $task,
    ?string          $modelName = null,
    bool             $quantized = true,
    ?array           $config = null,
    ?string          $cacheDir = null,
    string           $revision = 'main',
    ?string          $modelFilename = null,
    ?OutputInterface $output = null
): Pipeline
{
    if (is_string($task)) {
        $stringTask = $task;
        $task = Task::tryFrom($stringTask);

        if ($task === null) {
            throw UnsupportedTaskException::make($stringTask);
        }
    }

    $modelName ??= $task->defaultModelName();

    $model = $task->autoModel($modelName, $quantized, $config, $cacheDir, $revision, $modelFilename, $output);

    $tokenizer = $task->autoTokenizer($modelName, $cacheDir, $revision, $output);

    $processor = $task->autoProcessor($modelName, $config, $cacheDir, $revision, $output);

    return $task->pipeline($model, $tokenizer, $processor);
}
