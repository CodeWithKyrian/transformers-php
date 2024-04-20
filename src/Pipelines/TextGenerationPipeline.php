<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\GenerationConfig;
use function Codewithkyrian\Transformers\Utils\camelCaseToSnakeCase;

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
            $snakeCasedArgs[camelCaseToSnakeCase($key)] = $value;
        }

        $generationConfig = new GenerationConfig($snakeCasedArgs);

        $isChatMode = $this->isChatMode($inputs);

        if ($isChatMode) {
            $inputs = $this->tokenizer->applyChatTemplate($inputs, addGenerationPrompt: true, tokenize: false);
        }

        $isBatched = is_array($inputs);

        if (!$isBatched) {
            $inputs = [$inputs];
        }

        // By default, do not add special tokens
        $addSpecialTokens = $this->model->config['add_special_tokens'] ?? false;

        $this->tokenizer->paddingSide = 'left';
        ['input_ids' => $inputIds, 'attention_mask' => $attentionMask] = $this->tokenizer->tokenize(
            $inputs,
            padding: true,
            addSpecialTokens: $addSpecialTokens,
            truncation: true
        );

        $outputTokenIds = $this->model->generate(
            $inputIds,
            generationConfig: $generationConfig,
            inputsAttentionMask: $attentionMask,
            streamer: $streamer
        );

        $decoded = $this->tokenizer->batchDecode($outputTokenIds, skipSpecialTokens: true);


        $toReturn = array_fill(0, count($inputs), []);

        for ($i = 0; $i < count($decoded); ++$i) {
            $textIndex = floor($i / count($outputTokenIds) * count($inputs));
            $toReturn[$textIndex][] = [
                'generated_text' => $decoded[$i]
            ];
        }

        return (!$isBatched && count($toReturn) === 1) ? $toReturn[0] : $toReturn;

    }

    // Detect chat mode
    protected function isChatMode(string|array $texts): bool
    {
        return is_array($texts) && isset($texts[0]) && is_array($texts[0]) && !array_is_list($texts[0]);

    }
}