<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Generation\Streamers\Streamer;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use function Codewithkyrian\Transformers\Utils\array_every;
use function Codewithkyrian\Transformers\Utils\array_pop_key;
use function Codewithkyrian\Transformers\Utils\array_keys_to_snake_case;
use function Codewithkyrian\Transformers\Utils\camelCaseToSnakeCase;
use function Codewithkyrian\Transformers\Utils\timeUsage;

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
        /** @var Streamer $streamer */
        $streamer = array_pop_key($args, 'streamer');

        $returnFullText = array_pop_key($args, 'returnFullText', true);

        $kwargs = array_keys_to_snake_case($args);

        $generationConfig = new GenerationConfig($kwargs);

        $isBatched = false;
        $isChatInput = false;

        // Normalize inputs
        $texts = [];

        if (is_string($inputs)) {
            $texts = $inputs = [$inputs];
        } elseif (is_array($inputs) && array_every($inputs, fn($x) => is_string($x))) {
            $isBatched = true;
            $texts = $inputs;
        } else {
            if ($this->isChat($inputs)) {
                $inputs = [$inputs];
            } elseif (is_array($inputs) && array_every($inputs, [$this, 'isChat'])) {
                $isBatched = true;
            } else {
                throw new \Exception('Input must be a string, an array of strings, a Chat, or an array of Chats');
            }
            $isChatInput = true;

            // If the input is a chat, apply the chat template
            $texts = array_map(fn($x) => $this->tokenizer->applyChatTemplate($x, addGenerationPrompt: true, tokenize: false), $inputs);
        }

        // By default, do not add special tokens
        $addSpecialTokens = $generationConfig['add_special_tokens'] ?? false;

        $returnFullText = $isChatInput ? false : $returnFullText;

        $this->tokenizer->paddingSide = 'left';
        ['input_ids' => $inputIds, 'attention_mask' => $attentionMask] = $this->tokenizer->tokenize(
            $texts,
            padding: true,
            addSpecialTokens: $addSpecialTokens,
            truncation: true
        );

        $streamer?->setTokenizer($this->tokenizer)?->setPromptTokens($inputIds[0]->toArray());

        $outputTokenIds = $this->model->generate($inputIds,
            generationConfig: $generationConfig,
            inputsAttentionMask: $attentionMask,
            streamer: $streamer
        );

        $decoded = $this->tokenizer->batchDecode($outputTokenIds, skipSpecialTokens: true);

        $promptLengths = null;
        if (!$returnFullText && $inputIds->shape()[count($inputIds->shape()) - 1] > 0) {
            $promptLengths = array_map(fn($x) => mb_strlen($x), $this->tokenizer->batchDecode($inputIds->toArray(), skipSpecialTokens: true));
        }

        $toReturn = array_fill(0, count($inputs), []);

        for ($i = 0; $i < count($decoded); ++$i) {
            $textIndex = floor($i / count($outputTokenIds) * count($inputs));

            if ($promptLengths !== null) {
                // Trim the decoded text to only include the generated part
                $decoded[$i] = substr($decoded[$i], $promptLengths[$textIndex]);

                // Remove the leading space
                $decoded[$i] = ltrim($decoded[$i]);
            }

            $toReturn[$textIndex][] = [
                'generated_text' => $isChatInput
                    ? array_merge($inputs[$textIndex], [
                        ['role' => 'assistant', 'content' => $decoded[$i]]
                    ])
                    : $decoded[$i],
            ];
        }

        return (!$isBatched && count($toReturn) === 1) ? $toReturn[0] : $toReturn;

    }

    // Detect chat mode
    function isChat($x): bool
    {
        return is_array($x) && array_every($x, fn($item) => isset($item['role']) && isset($item['content']));
    }

}