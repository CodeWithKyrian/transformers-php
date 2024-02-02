<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\Math;

/**
 * Question answering pipeline
 *
 * *Example:** Extractive question-answering w/ `Xenova/distilbert-base-uncased-distilled-squad`.
 * ```php
 * use function Codewithkyrian\Transformers\pipeline;
 *
 * $qa = pipeline('question-answering', 'Xenova/distilbert-base-uncased-distilled-squad');
 *
 * $result = $qa('What is the capital of France?', 'The capital of France is Paris.');
 * // $result = ['answer' => 'Paris', 'score' => 0.997]
 * ```
 */
class QuestionAnsweringPipeline extends Pipeline
{
    public function __invoke(...$args): array
    {
        $question = $args[0];
        $context = $args[1] ?? $args["context"];
        $topk = $args["topk"] ?? 1;

        $inputs = $this->tokenizer->__invoke($question, $context, padding: true, truncation: true);

        $outputs = $this->model->__invoke($inputs);

        $toReturn = [];

        for ($i = 0; $i < $outputs['start_logits']->shape()[0]; ++$i) {
            $ids = $inputs['input_ids']->toArray()[$i];
            $sepIndex = array_search($this->tokenizer->sepTokenId, $ids);

            $startLogits = $outputs['start_logits'][$i]->buffer()->toArray();
            $endLogits = $outputs['end_logits'][$i]->buffer()->toArray();

            // Compute softmax for start and end logits and filter based on separator index
            $s1 = array_filter(
                array_map(
                    fn($x) => [$x[0], $x[1]],
                    array_map(null, Math::softmax($startLogits), range(0, count($startLogits) - 1))
                ),
                fn($x) => $x[1] > $sepIndex
            );


            $e1 = array_filter(
                array_map(
                    fn($x) => [$x[0], $x[1]],
                    array_map(null, Math::softmax($endLogits), range(0, count($endLogits) - 1))
                ),
                fn($x) => $x[1] > $sepIndex
            );

            // Compute the Cartesian product of start and end logits
            $product = Math::product($s1, $e1);

            // Filter options and compute values
            $options = array_filter($product, function ($x) {
                return $x[0][1] <= $x[1][1];
            });

            // Map options to desired format and sort
            $options = array_map(function ($x) {
                return [$x[0][1], $x[1][1], $x[0][0] * $x[1][0]];
            }, $options);

            // Sort by score
            usort($options, function ($a, $b) {
                return $b[2] <=> $a[2];
            });

            $minLength = min(count($options), $topk);

            for ($k = 0; $k < $minLength; ++$k) {
                [$start, $end, $score] = $options[$k];

                $answer_tokens = array_slice($ids, $start, $end - $start + 1);

                $answer = $this->tokenizer->decode($answer_tokens, skipSpecialTokens: true);

                $toReturn[] = ['answer' => $answer, 'score' => $score];
            }
        }

        return $topk === 1 ? $toReturn[0] : $toReturn;
    }
}