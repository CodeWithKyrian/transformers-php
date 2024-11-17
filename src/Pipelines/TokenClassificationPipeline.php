<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Generation\AggregationStrategy;
use Codewithkyrian\Transformers\Models\Output\TokenClassifierOutput;
use Exception;

/**
 * Named Entity Recognition pipeline using any `ModelForTokenClassification`.
 *
 * *Example:** Perform named entity recognition with `Xenova/bert-base-NER`.
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $classifier = pipeline('token-classification', 'Xenova/bert-base-NER');
 *
 * $output = $classifier('My name is Sarah and I live in London');
 * // $output => [
 * //     ["entity_group" => "PER", "score" => 0.9980202913284302, "word" => "Sarah"],
 * //     ["entity_group" => "LOC", "score" => 0.9994474053382874, "word" => "London"],
 * // ]
 * ```
 *
 * *Example:** Perform named entity recognition with `Xenova/bert-base-NER` (and return all labels).
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $classifier = pipeline('token-classification', 'Xenova/bert-base-NER');
 *
 * $output = $classifier('My name is Sarah and I live in the United States of America', ignoreLabels: []);
 * // $output => [
 * //   { entity_group: 'PER', score: 0.9966587424278259, word: 'Sarah' },
 * //   { entity: 'O', score: 0.9987385869026184, word: 'lives in the' },
 * //   { entity_group: 'LOC', score: 0.9995510578155518, word: 'United States of America' },
 * // ]
 * ```
 */
class TokenClassificationPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $ignoreLabels = $args['ignoreLabels'] ?? ['O'];
        $aggregationStrategy = $args['aggregationStrategy'] ?? AggregationStrategy::NONE;

        $aggregationStrategy = $aggregationStrategy instanceof AggregationStrategy
            ? $aggregationStrategy
            : AggregationStrategy::from($aggregationStrategy);

        $isBatched = is_array($inputs);
        if (!$isBatched) {
            $inputs = [$inputs];
        }

        $modelInputs = $this->tokenizer->__invoke($inputs, padding: true, truncation: true);

        /** @var TokenClassifierOutput $outputs */
        $outputs = $this->model->__invoke($modelInputs);

        $logits = $outputs->logits;
        $id2label = $this->model->config['id2label'];


        $toReturn = [];
        for ($i = 0; $i < $logits->shape()[0]; ++$i) {
            $ids = $modelInputs['input_ids'][$i];
            $batch = $logits[$i];

            $entities = [];

            for ($j = 0; $j < $batch->shape()[0]; ++$j) {
                $topScoreIndex = $batch[$j]->argMax();

                $entity = $id2label[$topScoreIndex] ?? "LABEL_{$topScoreIndex}";

                // TODO: add option to keep special tokens?
                $word = $this->tokenizer->decode([$ids[$j]], skipSpecialTokens: true);

                if ($word === '') {
                    // Was a special token. So, we skip it.
                    continue;
                }

                $scores = $batch[$j]->softmax();

                $entities[] = [
                    'entity' => $entity,
                    'score' => $scores[$topScoreIndex],
                    'index' => $j,
                    'word' => $word,

                    // TODO: null for now, but will add
                    'start' => null,
                    'end' => null,
                ];
            }

            $entities = $this->aggregateWords($entities, $aggregationStrategy);

            if ($aggregationStrategy === AggregationStrategy::NONE) {
                $entities = array_filter($entities, fn($token) => !in_array($token['entity'], $ignoreLabels));
            } else {
                $entities = $this->groupEntities($entities);
                $entities = array_filter($entities, fn($token) => !in_array($token['entity_group'], $ignoreLabels));
            }

            $toReturn[] = $entities;
        }

        return $isBatched ? $toReturn : $toReturn[0];
    }

    /**
     * Override tokens from a given word that disagree to force agreement on word boundaries.
     *
     * Example: micro|soft| com|pany| B-ENT I-NAME I-ENT I-ENT will be rewritten with first strategy as microsoft|
     * company| B-ENT I-ENT
     * @param array $entities The entities to aggregate.
     * @param AggregationStrategy $aggregationStrategy The strategy to use for aggregation.
     * @return array
     */
    protected function aggregateWords(array $entities, AggregationStrategy $aggregationStrategy): array
    {
        if ($aggregationStrategy == AggregationStrategy::NONE) {
            return $entities;
        }

        $wordEntities = [];
        $wordGroup = null;
        foreach ($entities as $entity) {
            if ($wordGroup === null) {
                $wordGroup = [$entity];
            } elseif ($this->tokenizer->model->continuingSubwordPrefix != null && str_starts_with($entity['word'], $this->tokenizer->model->continuingSubwordPrefix)) {
                $wordGroup[] = $entity;
            } else {
                $wordEntities[] = $this->aggregateWord($wordGroup, $aggregationStrategy);
                $wordGroup = [$entity];
            }
        }
        if ($wordGroup !== null) {
            $wordEntities[] = $this->aggregateWord($wordGroup, $aggregationStrategy);
        }

        return $wordEntities;
    }

    protected function aggregateWord(array $entities, AggregationStrategy $aggregationStrategy): array
    {
        switch ($aggregationStrategy) {
            case AggregationStrategy::FIRST:
                $score = $entities[0]['score'];
                $entity = $entities[0]['entity'];
                break;

            case AggregationStrategy::MAX:
                $score = max(array_column($entities, 'score'));
                $entity = $entities[array_search($score, array_column($entities, 'score'))]['entity'];
                break;

            case AggregationStrategy::AVERAGE:
                $score = array_sum(array_column($entities, 'score')) / count($entities);
                $entity = $entities[array_search(max(array_column($entities, 'score')), array_column($entities, 'score'))]['entity'];
                break;

            default:
                throw new Exception("Invalid aggregation_strategy");
        }


        $words = array_map(function ($word) {
            if ($this->tokenizer->model->continuingSubwordPrefix != null && str_starts_with($word, $this->tokenizer->model->continuingSubwordPrefix)) {
                return substr($word, strlen($this->tokenizer->model->continuingSubwordPrefix));
            }
            return $word;
        }, array_column($entities, 'word'));

        $word = implode('', $words);

        return [
            'entity' => $entity,
            'score' => $score,
            'word' => $word,
//            'start' => $entities[0]['start'],
//            'end' => end($entities)['end'],
            'start' => null,
            'end' => null,
        ];
    }

    protected function groupEntities($entities): array
    {
        $entityGroups = [];
        $entityGroupDisagg = [];

        foreach ($entities as $entity) {
            if (empty($entityGroupDisagg)) {
                $entityGroupDisagg[] = $entity;
                continue;
            }

            [$bi, $tag] = $this->getTag($entity['entity']);
            [$lastBi, $lastTag] = $this->getTag(end($entityGroupDisagg)['entity']);

            if ($tag === $lastTag && $bi !== "B") {
                $entityGroupDisagg[] = $entity;
            } else {
                $entityGroups[] = $this->groupSubEntities($entityGroupDisagg);
                $entityGroupDisagg = [$entity];
            }
        }

        if (!empty($entityGroupDisagg)) {
            $entityGroups[] = $this->groupSubEntities($entityGroupDisagg);
        }

        return $entityGroups;
    }

    protected function getTag($entityName): array
    {
        if (str_starts_with($entityName, "B-")) {
            return ["B", substr($entityName, 2)];
        } elseif (str_starts_with($entityName, "I-")) {
            return ["I", substr($entityName, 2)];
        } else {
            return ["I", $entityName]; // Default to "I" for continuation
        }
    }

    /**
     * Group together the adjacent tokens with the same entity predicted.
     *
     * Example: 'New York' is a single entity, but it's split into two tokens. This function groups them together.
     * @param array $entities
     * @return array
     */
    public function groupSubEntities(array $entities): array
    {
        $entity = explode("-", $entities[0]['entity'], 2)[1] ?? $entities[0]['entity'];
        $scores = array_column($entities, 'score');
        $averageScore = array_sum($scores) / count($scores);
        $word = implode(' ', array_column($entities, 'word'));

        return [
            'entity_group' => $entity,
            'score' => $averageScore,
            'word' => $word,
            'start' => null,
            'end' => null
        ];
    }

}
