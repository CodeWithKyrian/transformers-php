<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Generation\Streamers\Streamer;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use function Codewithkyrian\Transformers\Utils\array_pop_key;
use function Codewithkyrian\Transformers\Utils\array_keys_to_snake_case;

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
        /** @var Streamer $streamer */
        $streamer = array_pop_key($args, 'streamer');

        $kwargs = array_keys_to_snake_case($args);

        $generateKwargs = new GenerationConfig($kwargs);


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

        $inputs = $this instanceof TranslationPipeline && method_exists($tokenizer, 'buildTranslationInputs')
            ? $tokenizer->buildTranslationInputs($inputs, $generateKwargs, padding: true, truncation: true)
            : $tokenizer->__invoke($inputs, padding: true, truncation: true);

        // Streamer can only handle one input at a time for now, so we only pass the first input
        $streamer?->setTokenizer($this->tokenizer)?->shouldSkipPrompt(false);

        // Generate output token ids
        $outputTokenIds = $this->model->generate(
            $inputs['input_ids'],
            generationConfig: $generateKwargs,
            streamer: $streamer,
            attentionMask: $inputs['attention_mask']
        );

        // Decode token ids to text
        return array_map(
            fn($text) => [$this->key => $text],
            $tokenizer->batchDecode($outputTokenIds->toArray(), skipSpecialTokens: true)
        );
    }
}
