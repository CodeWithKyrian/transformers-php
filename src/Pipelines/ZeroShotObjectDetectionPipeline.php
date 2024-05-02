<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Output\ObjectDetectionOutput;
use Codewithkyrian\Transformers\Utils\Tensor;
use function Codewithkyrian\Transformers\Utils\getBoundingBox;
use function Codewithkyrian\Transformers\Utils\prepareImages;

/**
 * Zero-shot object detection pipeline. This pipeline predicts bounding boxes of
 * objects when you provide an image and a set of `candidate_labels`.
 *
 * **Example:** Zero-shot object detection w/ `Xenova/owlvit-base-patch32`.
 * ```php
 * $detector = pipeline('zero-shot-object-detection', 'Xenova/owlvit-base-patch32');
 * $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/astronaut.png';
 * $candidateLabels = ['human face', 'rocket', 'helmet', 'american flag'];
 * $output = $detector($url, $candidateLabels);
 * // [
 * //   [
 * //     score: 0.24392342567443848,
 * //     label: 'human face',
 * //     box: { xmin: 180, ymin: 67, xmax: 274, ymax: 175 }
 * //   ],
 * //   ...
 * // ]
 * ```
 *
 * **Example:** Zero-shot object detection w/ `Xenova/owlvit-base-patch32` (returning top 4 matches and setting a threshold).
 * ```javascript
 * $detector = pipeline('zero-shot-object-detection', 'Xenova/owlvit-base-patch32');
 * $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/beach.png';
 * $candidateLabels = ['hat', 'book', 'sunglasses', 'camera'];
 * $output = $detector($url, $candidateLabels, topK : 4, threshold : 0.05);
 * // [
 * //   [
 * //     score: 0.1606510728597641,
 * //     label: 'sunglasses',
 * //     box: { xmin: 347, ymin: 229, xmax: 429, ymax: 264 }
 * //   ],
 * //   ...
 * // ]
 * ```
 */
class ZeroShotObjectDetectionPipeline extends Pipeline
{

    public function __invoke(array|string $inputs, ...$args): array
    {
        $candidateLabels = $args[0];
        $threshold = $args['threshold'] ?? 0.1;
        $topK = $args['topK'] ?? null;
        $percentage = $args['percentage'] ?? false;

        $isBatched = is_array($inputs);

        $preparedImages = prepareImages($inputs);

        // Run tokenization
        $textInputs = $this->tokenizer->tokenize($candidateLabels, padding: true, truncation: true);

        // Run processor
        $modelInputs = ($this->processor)($preparedImages);

        $toReturn = [];
        foreach ($preparedImages as $i => $image) {
            $imageSize = $percentage ? null : [[$image->height(), $image->width()]];
            $pixelValues = $modelInputs['pixel_values'][$i]->unsqueeze(0);

            // Run model with both text and pixel inputs
            /** @var ObjectDetectionOutput $output */
            $output = $this->model->__invoke(array_merge($textInputs, ['pixel_values' => $pixelValues]));

            // Perform post-processing
            $processed = $this->processor->featureExtractor->postProcessObjectDetection($output, $threshold, $imageSize, true)[0];

            $result = [];

            foreach ($processed['boxes'] as $j => $box) {
                $result[] = [
                    'score' => $processed['scores'][$j],
                    'label' => $candidateLabels[$processed['classes'][$j]],
                    'box' => getBoundingBox($box, !$percentage),
                ];
            }
            // Sort by score
            usort($result, fn($a, $b) => $b['score'] <=> $a['score']);

            if ($topK !== null) {
                $result = array_slice($result, 0, $topK);
            }

            $toReturn[] = $result;
        }

        return $isBatched ? $toReturn : $toReturn[0];
    }
}