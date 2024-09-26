<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Output\MaskedLMOutput;
use Codewithkyrian\Transformers\Pipelines\Pipeline;
use Codewithkyrian\Transformers\Utils\Math;

use function Codewithkyrian\Transformers\Utils\array_pop_key;

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
    public function __invoke(array|string $inputs, ...$args): array
    {
        $topK = array_pop_key($args, 'topK', 5);

        $modelInputs = $this->tokenizer->__invoke($inputs, padding: true, truncation: true);

        /** @var MaskedLMOutput $outputs */
        $outputs = $this->model->__invoke($modelInputs);

        $toReturn = [];

        for ($i = 0; $i < $modelInputs['input_ids']->shape()[0]; ++$i) {
            $ids = $modelInputs['input_ids'][$i]->toArray();
            $maskTokenIndex = array_search($this->tokenizer->maskTokenId, $ids);

            if ($maskTokenIndex === false) {
                throw new \Error("Mask token ({$this->tokenizer->maskToken}) not found in text.");
            }

            $logits = $outputs->logits[$i][$maskTokenIndex];

            [$scores, $indices] = $logits->softmax()->topk($topK);

            $toReturn = [];

            foreach ($indices as $i => $index) {
                $sequence = $ids;
                $sequence[$maskTokenIndex] = $index;

                $toReturn[] = [
                    'score' => $scores[$i],
                    'token' => $index,
                    'token_str' => $this->tokenizer->decode([$index], skipSpecialTokens: true),
                    'sequence' => $this->tokenizer->decode($sequence, skipSpecialTokens: true),
                ];
            }
        }

        return is_array($inputs) ? $toReturn : $toReturn[0];
    }
}