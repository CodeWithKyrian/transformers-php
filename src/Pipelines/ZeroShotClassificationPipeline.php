<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;
use Codewithkyrian\Transformers\Models\Pretrained\PretrainedModel;
use Codewithkyrian\Transformers\PretrainedTokenizers\PreTrainedTokenizer;
use Codewithkyrian\Transformers\Utils\Math;
use function Codewithkyrian\Transformers\Utils\timeUsage;

/**
 * NLI-based zero-shot classification pipeline using any model that has been fine-tuned on NLI (natural language inference)
 * tasks. Equivalent of `text-classification` pipelines, but these models don't require a hardcoded number of potential
 * classes, they can be chosen at runtime. It usually means it's slower but it is **much** more flexible.
 *
 * *Example:** Zero shot classification with `Xenova/mobilebert-uncased-mnli`.
 * ```php
 * use Codewithkyrian\Transformers\pipeline;
 *
 * $classifier = pipeline('zero-shot-classification', 'Xenova/mobilebert-uncased-mnli');
 *
 * $result = $classifier('Who are you voting for in 2020?', ['politics', 'public health', 'economics', 'elections']);
 * // [
 * //   'sequence' => 'Who are you voting for in 2020?',
 * //   'labels' => ['elections', 'politics', 'economics', 'public health'],
 * //   'scores' => [0.7477419980308, 0.22676137303736, 0.022944918157188, 0.0025517107746598],
 * // ]
 *
 * ```
 *
 * **Example:** Zero shot classification with `Xenova/nli-deberta-v3-xsmall` (multi-label).
 * ```php
 * use Codewithkyrian\Transformers\pipeline;
 *
 * $classifier = pipeline('zero-shot-classification', 'Xenova/nli-deberta-v3-xsmall');
 *
 * $result = $classifier(
 *          'I have a problem with my iphone that needs to be resolved asap!',
 *          [ 'urgent', 'not urgent', 'phone', 'tablet', 'computer' ],
 *          multiLabel: true
 *     );
 *
 * // [
 * //   'sequence' => 'I have a problem with my iphone that needs to be resolved asap!',
 * //   'labels' => [ 'urgent', 'phone', 'computer', 'tablet', 'not urgent' ],
 * //   'scores' => [ 0.99588709563603, 0.9923963400697, 0.0023335396113424, 0.0015134149376, 0.0010699384208377 ]
 * // ]
 *
 * ```
 */
class ZeroShotClassificationPipeline extends Pipeline
{
    protected array $label2id;

    protected mixed $entailmentId;

    protected mixed $contradictionId;

    public function __construct(Task|string $task, PretrainedModel $model, ?PreTrainedTokenizer $tokenizer = null, ?string $processor = null)
    {
        parent::__construct($task, $model, $tokenizer, $processor);

        $this->label2id = array_change_key_case($this->model->config['label2id']);

        $this->entailmentId = $this->label2id['entailment'] ?? null;

        if ($this->entailmentId === null) {
            echo "Could not find 'entailment' in label2id mapping. Using 2 as entailment_id.\n";
            $this->entailmentId = 2;
        }

        $this->contradictionId = $this->label2id['contradiction'] ?? $this->label2id['not_entailment'] ?? null;
        if ($this->contradictionId === null) {
            echo "Could not find 'contradiction' in label2id mapping. Using 0 as contradiction_id.\n";
            $this->contradictionId = 0;
        }
    }

    public function __invoke(array|string $inputs, ...$args): array
    {
        $candidateLabels = $args[0];
        $multiLabel = $args['multiLabel'] ?? false;
        $hypothesisTemplate = $args['hypothesisTemplate'] ?? "This example is {}.";

        $isBatched = is_array($inputs);

        if (!$isBatched) {
            $inputs = [$inputs];
        }

        if (!is_array($candidateLabels)) {
            $candidateLabels = [$candidateLabels];
        }

        // Insert labels into hypothesis template
        $hypotheses = array_map(fn($x) => str_replace('{}', $x, $hypothesisTemplate), $candidateLabels);

        // Determine whether to perform softmax over each label independently
        $softmaxEach = $multiLabel || count($candidateLabels) === 1;

        $toReturn = [];
        foreach ($inputs as $premise) {
            $entailsLogits = [];

            foreach ($hypotheses as $hypothesis) {
                $inputs = $this->tokenizer->tokenize($premise, textPair: $hypothesis, padding: true, truncation: true);

                /** @var SequenceClassifierOutput $outputs */
                $outputs = $this->model->__invoke($inputs);


                if ($softmaxEach) {
                    $entailsLogits[] = [
                        $outputs->logits->buffer()[$this->contradictionId],
                        $outputs->logits->buffer()[$this->entailmentId]
                    ];
                } else {
                    $entailsLogits[] = $outputs->logits->buffer()[$this->entailmentId];
                }

            }

            $scores = $softmaxEach
                ? array_map(fn($x) => Math::softmax($x)[1], $entailsLogits)
                : Math::softmax($entailsLogits);

            // Sort by scores (desc) and return scores with indices
            $scores = array_map(fn($x, $i) => [$x, $i], $scores, array_keys($scores));
            usort($scores, fn($a, $b) => $b[0] <=> $a[0]);

            $toReturn[] = [
                'sequence' => $premise,
                'labels' => array_map(fn($x) => $candidateLabels[$x[1]], $scores),
                'scores' => array_map(fn($x) => array_shift($x), $scores),
            ];
        }

        return $isBatched ? $toReturn : $toReturn[0];
    }
}
