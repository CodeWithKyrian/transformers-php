<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Tensor;
use function Codewithkyrian\Transformers\timeUsage;

class Text2TextGenerationPipeline extends Pipeline
{
    protected string $key = 'generated_text';

    public function __invoke(...$args): array
    {
        $texts = array_shift($args);

        $streamer = null;

        if(array_key_exists('streamer', $args))
        {
            $streamer = $args['streamer'];
            unset($args['streamer']);
        }


        // Convert the rest of the arguments key names from camelCase to snake_case
        $snakeCasedArgs = [];
        foreach ($args as $key => $value) {
            $snakeCasedArgs[$this->camelCaseToSnakeCase($key)] = $value;
        }

        $generateKwargs = new GenerationConfig($snakeCasedArgs);

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
        $outputTokenIds = $this->model->generate($inputIds, generationConfig: $generateKwargs, streamer: $streamer);

        // Decode token ids to text
        return array_map(
            fn($text) => [$this->key => $text],
            $tokenizer->batchDecode($outputTokenIds, skipSpecialTokens: true)
        );
    }

    protected function camelCaseToSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}