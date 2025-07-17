<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Tensor\Tensor;
use function Codewithkyrian\Transformers\Utils\array_pop_key;

/**
 * Feature extraction pipeline using no model head. This pipeline extracts the hidden
 *  states from the base transformer, which can be used as features in downstream tasks.
 *
 * **Example:** Run feature extraction with `bert-base-uncased` (without pooling/normalization).
 *
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $extractor = pipeline('feature-extraction', 'Xenova/bert-base-uncased', revision: 'default');
 * $features = $extractor('This is a simple test.');
 * // Tensor {
 * //   shape: [1, 8, 768]
 * //   dtype: float32
 * //   buffer: [
 * //     -0.0123, 0.0456, 0.89, ..., -0.123, 0.456, 0.789
 * //   ]
 * // }
 *
 * ```
 *
 * **Example:** Run feature extraction with `bert-base-uncased` (with pooling/normalization).
 *
 * ```php
 *
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $extractor = pipeline('feature-extraction', 'Xenova/bert-base-uncased', revision: 'default');
 * $features = $extractor('This is a simple test.', pooling: 'mean', normalize: true);
 * // Tensor {
 * //   shape: [1, 768]
 * //   dtype: float32
 * //   buffer: [
 * //     -0.0123, 0.0456, 0.89, ..., -0.123, 0.456, 0.789
 * //   ]
 * // }
 * ```
 *
 * **Example:** Calculating embeddings with `sentence-transformers` models.
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $extractor = pipeline('feature-extraction', 'sentence-transformers/paraphrase-MiniLM-L6-v2');
 * $features = $extractor('This is a simple test.', pooling: 'mean', normalize: true);
 * // Tensor {
 * //   shape: [1, 384]
 * //   dtype: float32
 * //   buffer: [
 * //     -0.0123, 0.0456, 0.89, ..., -0.123, 0.456, 0.789
 * //   ]
 * // }
 * ```
 *
 */
class FeatureExtractionPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $pooling = array_pop_key($args, 'pooling', 'none');
        $normalize = array_pop_key($args, 'normalize', false);

        $modelInputs = $this->tokenizer->__invoke($inputs, padding: true, truncation: true);

        $outputs = $this->model->__invoke($modelInputs);

        /** @var Tensor $result */
        $result = $outputs["last_hidden_state"] ?? $outputs["logits"] ?? $outputs["token_embeddings"];

        switch ($pooling) {
            case 'none':
                // No pooling, return the full tensor
                break;

            case 'mean':
                $result = $result->meanPooling($modelInputs["attention_mask"]);
                break;

            case 'first_token':
            case 'cls':
                $result = $result->slice(null, 0);
                break;

            case 'last_token':
            case 'eos':
                $result = $result->slice(null, -1);
                break;

            default:
                throw new \Error("Pooling method not supported. Please use 'mean',  'cls', 'first_token', 'last_token', or 'none'.");
        }

        if ($normalize) {
            $result = $result->normalize(2, -1);
        }

        return $result->toArray();
    }
}
