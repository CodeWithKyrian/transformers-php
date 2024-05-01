<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;
use Codewithkyrian\Transformers\Utils\Math;
use Codewithkyrian\Transformers\Utils\Tensor;

/**
 * Text classification pipeline
 *
 * *Example:** Sentiment-analysis w/ `Xenova/distilbert-base-uncased-finetuned-sst-2-english`.
 * ```php
 * use function Codewithkyrian\Transformers\pipeline;
 *
 * $classifier = pipeline('sentiment-analysis', 'Xenova/distilbert-base-uncased-finetuned-sst-2-english');
 *
 * $result = $classifier('I love you');
 * // $result = ['label' => 'POSITIVE', 'score' => 0.9998]
 *
 * $result = $classifier('I hate you');
 * // $result = ['label' => 'NEGATIVE', 'score' => 0.9997]
 *
 *
 * *Example:** Multilingual sentiment-analysis w/ `Xenova/bert-base-multilingual-uncased-sentiment` (and return top 5 classes).
 * ```php
 * use function Codewithkyrian\Transformers\pipeline;
 *
 * $classifier = pipeline('sentiment-analysis', 'Xenova/bert-base-multilingual-uncased-sentiment', topk: null);
 *
 * $result = $classifier('Le meilleur film de tous les temps.', topk: 5);
 *  // [
 *  //   { label: '5 stars', score: 0.9610759615898132 },
 *  //   { label: '4 stars', score: 0.03323351591825485 },
 *  //   { label: '3 stars', score: 0.0036155181005597115 },
 *  //   { label: '1 star', score: 0.0011325967498123646 },
 *  //   { label: '2 stars', score: 0.0009423971059732139 }
 *  // ]
 *
 * *Example:** Toxic comment classification w/ `Xenova/toxic-bert` (and return all classes).
 *
 * ```php
 * use function Codewithkyrian\Transformers\pipeline;
 *
 * $classifier = pipeline('text-classification', 'Xenova/toxic-bert', topk: null);
 * $result = $classifier('I hate you!', topk: null);
 * // [
 *  //   { label: 'toxic', score: 0.9593140482902527 },
 *  //   { label: 'insult', score: 0.16187334060668945 },
 *  //   { label: 'obscene', score: 0.03452680632472038 },
 *  //   { label: 'identity_hate', score: 0.0223250575363636 },
 *  //   { label: 'threat', score: 0.019197041168808937 },
 *  //   { label: 'severe_toxic', score: 0.005651099607348442 }
 *  // ]
 */
class TextClassificationPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $topK = $args["topK"] ?? 1;

        $modelInputs = $this->tokenizer->tokenize($inputs, padding: true, truncation: true);

        /** @var SequenceClassifierOutput $outputs */
        $outputs = $this->model->__invoke($modelInputs);

        $problemType = $this->model->config['problem_type'] ?? 'single_label_classification';

        $activationFunction = $problemType == 'multi_label_classification' ?
            fn(Tensor $batch) => $batch->sigmoid() :
            fn(Tensor $batch) => $batch->softmax();

        $id2label = $this->model->config['id2label'];
        $toReturn = [];

        foreach ($outputs->logits as $batch) {
            $output = $activationFunction($batch);

            [$scores, $indices] = $output->topk($topK);

            $values = [];

            foreach ($indices as $i => $index) {
                $values[] = ['label' => $id2label[$index], 'score' => $scores[$i]];
            }

            if ($topK === 1) {
                $toReturn = $values;
            } else {
                $toReturn[] = $values;
            }
        }

        return $toReturn[0];
    }
}