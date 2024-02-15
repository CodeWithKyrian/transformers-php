<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Tensor;

class Text2TextGenerationPipeline extends Pipeline
{
    protected $key = 'generated_text';

    public function __invoke(...$args): array|Tensor
    {
        $texts = array_shift($args);

        $generateKwargs = new GenerationConfig($args);

        if (!is_array($texts)) {
            $texts = [$texts];
        }

        // Add global prefix, if present
        $prefix = $this->model->config['prefix'] ?? null;
        if ($prefix) {
            $texts = array_map(fn($x) => $prefix . $x, $texts);
        }

        // Handle task specific params
        $taskSpecificParams = $this->model->config['task_specific_params'] ?? null;


        if ($taskSpecificParams && isset($taskSpecificParams[$this->task->value])) {
            // Add prefixes, if present
            $taskPrefix = $taskSpecificParams[$this->task->value]['prefix'] ?? null;

            if ($taskPrefix) {
                $texts = array_map(fn($x) => $taskPrefix . $x, $texts);
            }

            // TODO: update generation config
        }

        // Tokenize texts
        $tokenizer = $this->tokenizer;

        $inputIds = $this instanceof TranslationPipeline && method_exists($tokenizer, 'buildTranslationInputs')
            ? $tokenizer->buildTranslationInputs($texts, $generateKwargs, padding: true, truncation: true)['input_ids']
            : $tokenizer->__invoke($texts, padding: true, truncation: true)['input_ids'];

        // Generate output token ids
        $outputTokenIds = $this->model->generate($inputIds, $generateKwargs);

        // Decode token ids to text
        return array_map(
            fn($text) => [$this->key => $text],
            $tokenizer->batchDecode($outputTokenIds, skipSpecialTokens: true)
        );
    }
}