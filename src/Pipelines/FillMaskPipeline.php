<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Pipelines\Pipeline;
use Codewithkyrian\Transformers\Utils\Math;

/**
 * Masked language modeling prediction pipeline.
 *
 * This pipeline only works for models that have a LM head. Not all models have one.
 *
 * *Example:** Fill mask w/ `Xenova/bert-base-uncased`.
 *
 * ```php
 * use function Codewithkyrian\Transformers\pipeline;
 *
 * $pipeline = pipeline('fill-mask', 'Xenova/bert-base-uncased');
 *
 * $result = $pipeline('The quick brown [MASK] jumps over the lazy dog.');
 * // [
 * //   { score: 0.2961, token: 'fox', token_str: 'fox', sequence: 'the quick brown fox jumps over the lazy dog.' },
 * //   { score: 0.1372, token: 'dog', token_str: 'dog', sequence: 'the quick brown dog jumps over the lazy dog.' },
 * //   { score: 0.0912, token: 'cat', token_str: 'cat', sequence: 'the quick brown cat jumps over the lazy dog.' },
 * //   { score: 0.0716, token: 'rabbit', token_str: 'rabbit', sequence: 'the quick brown rabbit jumps over the lazy dog.' },
 * //   { score: 0.0545, token: 'wolf', token_str: 'wolf', sequence: 'the quick brown wolf jumps over the lazy dog.' }
 * // ]
 */
class FillMaskPipeline extends Pipeline
{
    public function __invoke(...$args): array
    {
        $texts = $args[0];
        $topk = $args["topk"] ?? 5;

        $modelInputs = $this->tokenizer->__invoke($texts, padding: true, truncation: true);

        $outputs = $this->model->__invoke($modelInputs);

        $toReturn = [];

        for ($i = 0; $i < $modelInputs['input_ids']->shape()[0]; ++$i) {
            $ids = $modelInputs['input_ids']->toArray()[$i];
            $mask_token_index = array_search($this->tokenizer->maskTokenId, $ids);

            if ($mask_token_index === false) {
                throw new \Error("Mask token ({$this->tokenizer->maskToken}) not found in text.");
            }

            $logits = $outputs["logits"]->toArray()[$i];
            $itemLogits = $logits[$mask_token_index];

            $scores = Math::getTopItems(Math::softmax($itemLogits), $topk);

            $toReturn[] = array_map(function ($key, $value) use ($ids, $mask_token_index) {
                $sequence = $ids;
                $sequence[$mask_token_index] = $key;

                return [
                    'score' => $value,
                    'token' => $key,
                    'token_str' => $this->tokenizer->tokenizer->vocab[$key],
                    'sequence' => $this->tokenizer->decode($sequence, skipSpecialTokens: true),
                ];
            }, array_keys($scores), $scores);
        }

        return is_array($texts) ? $toReturn : $toReturn[0];
    }
}