<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Tensor;

/**
 * A pipeline for generating text using a model that performs text-to-text generation tasks.
 *
 * **Example:** Text-to-text generation w/ `Xenova/LaMini-Flan-T5-783M`.
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $generator = pipeline('text2text-generation', model: 'Xenova/LaMini-Flan-T5-783M');
 * $query = 'How many continents are there in the world?';
 *
 * $results = $generator($query, maxNewTokens: 128, repetitionPenalty: 1.6);
 * // ['generated_text' => 'There are 7 continents in the world.']
 *```
 */
class Text2TextGenerationPipeline extends Pipeline
{
    protected string $key = 'generated_text';

    public function __invoke(array|string $inputs, ...$args): array
    {
        $streamer = null;

        if (array_key_exists('streamer', $args)) {
            $streamer = $args['streamer'];
            unset($args['streamer']);
        }

        // Convert the rest of the arguments key names from camelCase to snake_case
        $snakeCasedArgs = [];
        foreach ($args as $key => $value) {
            $snakeCasedArgs[$this->camelCaseToSnakeCase($key)] = $value;
        }

        $generateKwargs = new GenerationConfig($snakeCasedArgs);


        if (!is_array($inputs)) {
            $inputs = [$inputs];
        }

        // Add global prefix, if present
        $prefix = $this->model->config['prefix'] ?? null;
        if ($prefix) {
            $inputs = array_map(fn($x) => $prefix . $x, $inputs);
        }

        // Handle task specific params
        $taskSpecificParams = $this->model->config['task_specific_params'] ?? null;


        if ($taskSpecificParams && isset($taskSpecificParams[$this->task->value])) {
            // Add prefixes, if present
            $taskPrefix = $taskSpecificParams[$this->task->value]['prefix'] ?? null;

            if ($taskPrefix) {
                $inputs = array_map(fn($x) => $taskPrefix . $x, $inputs);
            }

            // TODO: update generation config
        }

        // Tokenize texts
        $tokenizer = $this->tokenizer;

        $inputIds = $this instanceof TranslationPipeline && method_exists($tokenizer, 'buildTranslationInputs')
            ? $tokenizer->buildTranslationInputs($inputs, $generateKwargs, padding: true, truncation: true)['input_ids']
            : $tokenizer->__invoke($inputs, padding: true, truncation: true)['input_ids'];


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