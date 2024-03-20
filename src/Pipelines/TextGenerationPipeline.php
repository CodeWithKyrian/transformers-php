<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\GenerationConfig;

/**
 * Language generation pipeline using any `ModelWithLMHead` or `ModelForCausalLM`.
 *  This pipeline predicts the words that will follow a specified text prompt.
 *
 * *Example:** Text generation with `Xenova/distilgpt2` (default settings).
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $generator = pipeline('text-generation', model: 'Xenova/distilgpt2');
 *
 * $output = $generator('I enjoy walking with my cute dog,');
 * // ['generated_text' => 'I enjoy walking with my cute dog, and I love to play with the other dogs.']
 * ```
 *
 * *Example:** Text generation with `Xenova/distilgpt2` (custom settings).
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $generator = pipeline('text-generation', model: 'Xenova/distilgpt2');
 *
 * $output = $generator('Once upon a time, there was', maxNewTokens: 50, temperature: 2, repetitionPenalty: 2.5, numReturnSequences: 5);
 * // [
 * //     ['generated_text' => 'Once upon a time, there was a beautiful princess who lived in a castle.'],
 * //     ['generated_text' => 'Once upon a time, there was a great war between the humans and the elves.'],
 * // ]
 * ```
 *
 * *Example:** Run code generation with `Xenova/codegen-350M-mono`.
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $generator = pipeline('text-generation', model: 'Xenova/codegen-350M-mono');
 *
 * $output = $generator('function test() {', maxNewTokens: 100);
 * // ['generated_text' => 'function test() {\n  console.log("Hello, World!");\n}']
 * ```
 *
 *
 *
 */
class TextGenerationPipeline extends Pipeline
{
    public function __invoke(array|string $texts, ...$args): array
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

        $generationConfig = new GenerationConfig($snakeCasedArgs);

        $isBatched = is_array($texts);
        if (!$isBatched) {
            $texts = [$texts];
        }

        // By default, do not add special tokens
        $addSpecialTokens = $this->model->config['add_special_tokens'] ?? false;

        $this->tokenizer->paddingSide = 'left';
        ['input_ids' => $inputIds, 'attention_mask' => $attentionMask] = $this->tokenizer->tokenize(
            $texts,
            padding: true,
            addSpecialTokens: $addSpecialTokens,
            truncation: true
        );

        $outputTokenIds = $this->model->generate($inputIds, generationConfig: $generationConfig, streamer: $streamer);

        $decoded = $this->tokenizer->batchDecode($outputTokenIds, skipSpecialTokens: true);

        $toReturn = array_fill(0, count($texts), []);

        for ($i = 0; $i < count($decoded); ++$i) {
            $textIndex = floor($i / count($outputTokenIds) * count($texts));
            $toReturn[$textIndex][] = [
                'generated_text' => $decoded[$i]
            ];
        }

        return (!$isBatched && count($toReturn) === 1) ? $toReturn[0] : $toReturn;

    }

    protected function camelCaseToSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}