<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Output\ObjectDetectionOutput;
use function Codewithkyrian\Transformers\Utils\getBoundingBox;
use function Codewithkyrian\Transformers\Utils\prepareImages;

/**
 * Object detection pipeline using any `AutoModelForObjectDetection`.
 * This pipeline predicts bounding boxes of objects and their classes.
 *
 * **Example:** Run object-detection with `Xenova/detr-resnet-50`.
 * ```php
 * $detector = pipeline('object-detection', 'Xenova/detr-resnet-50');
 * $img = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/cats.jpg';
 * $output = $detector($img, threshold: 0.9);
 * // [
 * //   ['score' => 0.9976370930671692, 'label' => "remote", 'box' => ['xmin' => 29, 'ymin' => 65, 'xmax' => 188, 'ymax' => 122]],
 * //   ...
 * //   ['score' => 0.9984092116355896, 'label' => "cat", 'box' => ['xmin' => 332, 'ymin' => 21, 'xmax' => 648, 'ymax' => 366]]
 * // ]
 * ```
 */
class ObjectDetectionPipeline extends Pipeline
{

    public function __invoke(array|string $inputs, ...$args): array
    {
        $threshold = $options['threshold'] ?? 0.9;
        $percentage = $options['percentage'] ?? false;


        $isBatched = is_array($inputs);

        if ($isBatched && count($inputs) !== 1) {
            throw new \Exception("Object detection pipeline currently only supports a batch size of 1.");
        }

        $preparedImages = prepareImages($inputs);


        $imageSizes = $percentage ? null : array_map(fn($x) => [$x->height(), $x->width()], $preparedImages);

        ['pixel_values' => $pixelValues, 'pixel_mask' => $pixelMask] = ($this->processor)($preparedImages);

        /** @var ObjectDetectionOutput $output */
        $output = $this->model->__invoke(['pixel_values' => $pixelValues, 'pixel_mask' => $pixelMask]);

        $processed = $this->processor->featureExtractor->postProcessObjectDetection($output, $threshold, $imageSizes);

        $id2label = $this->model->config['id2label'];

        $result = [];
        foreach ($processed as $batch) {
            $boxes = $batch['boxes'];
            $scores = $batch['scores'];
            $classes = $batch['classes'];

            $batchResult = [];
            foreach ($boxes as $i => $box) {
                $batchResult[] = [
                    'score' => $scores[$i],
                    'label' => $id2label[$classes[$i]],
                    'box' => getBoundingBox($box, !$percentage),
                ];
            }

            $result[] = $batchResult;
        }

        return $isBatched ? $result : $result[0];
    }
}