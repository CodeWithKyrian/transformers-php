<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use function Codewithkyrian\Transformers\Utils\prepareImages;

/**
 * Image feature extraction pipeline using no model head. This pipeline extracts the hidden
 * states from the base transformer, which can be used as features in downstream tasks.
 *
 * **Example:** Perform image feature extraction with `Xenova/vit-base-patch16-224-in21k`.
 * ```php
 * $imageFeatureExtractor = pipeline('image-feature-extraction', 'Xenova/vit-base-patch16-224-in21k');
 * $url = 'https://huggingface.co/datasets/huggingface/documentation-images/resolve/main/cats.png';
 * $features = $imageFeatureExtractor($url);
 * // Tensor {
 * //   shape: [ 1, 197, 768 ],
 * //   buffer: [ ... ],
 * //   size: 151296
 * // }
 * ```
 *
 * **Example:** Compute image embeddings with `Xenova/clip-vit-base-patch32`.
 * ```javascript
 * $imageFeatureExtractor = await pipeline('image-feature-extraction', 'Xenova/clip-vit-base-patch32');
 * $url = 'https://huggingface.co/datasets/huggingface/documentation-images/resolve/main/cats.png';
 * $features = $imageFeatureExtractor(url);
 * // Tensor {
 * //   shape: [ 1, 512 ],
 * //   buffer: [ ... ],
 * //   size: 512
 * // }
 * ```
 */
class ImageFeatureExtractionPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $pool = $args['pool'] ?? null;
        $preparedImages = prepareImages($inputs);


        ['pixel_values' => $pixelValues] = ($this->processor)($preparedImages);

        $output = $this->model->__invoke(['pixel_values' => $pixelValues]);

        $result = [];

        if ($pool) {
            if (!isset($output['pooler_output'])) {
                throw new \Exception("No pooled output was returned. Make sure the model has a 'pooler' layer when using the 'pool' option.");
            }

            $result = $output['pooler_output'];
        } else {
            $result = $output['last_hidden_state'] ?? $output['logits'] ?? $output['image_embeds'];
        }

        return $result->toArray();
    }
}