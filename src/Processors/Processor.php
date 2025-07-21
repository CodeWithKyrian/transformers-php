<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Processors;

use Codewithkyrian\Transformers\FeatureExtractors\FeatureExtractor;
use Codewithkyrian\Transformers\Models\Output\ObjectDetectionOutput;
use Codewithkyrian\Transformers\Utils\Math;
use Exception;
use Codewithkyrian\Transformers\Transformers;

/**
 * Represents a Processor that extracts features from an input.
 */
class Processor
{
    public function __construct(
        public FeatureExtractor $featureExtractor
    ) {}

    /**
     * Post-processes the outputs of the model (for object detection).
     * @param ObjectDetectionOutput $outputs The outputs of the model that must be post-processed
     * @param float $threshold The threshold to use for the scores.
     * @param array|null $targetSizes The sizes of the original images.
     * @param bool $isZeroShot Whether zero-shot object detection was performed.
     * @return array An array of objects containing the post-processed outputs.
     */
    public static function postProcessObjectDetection(ObjectDetectionOutput $outputs, float $threshold = 0.5, ?array $targetSizes = null, bool $isZeroShot = false): array
    {
        $logger = Transformers::getLogger();
        $outLogits = $outputs->logits;
        $outBbox = $outputs->predBoxes;
        [$batchSize, $numBoxes, $numClasses] = $outLogits->shape();
        if ($targetSizes !== null && count($targetSizes) !== $batchSize) {
            $logger->warning('Target sizes count does not match batch size', [
                'targetSizes_count' => count($targetSizes),
                'batchSize' => $batchSize
            ]);
            throw new Exception("Make sure that you pass in as many target sizes as the batch dimension of the logits");
        }
        $toReturn = [];
        for ($i = 0; $i < $batchSize; ++$i) {
            $targetSize = $targetSizes !== null ? $targetSizes[$i] : null;
            $info = [
                'boxes' => [],
                'classes' => [],
                'scores' => []
            ];
            $logits = $outLogits[$i];
            $bbox = $outBbox[$i];
            $detectionCount = 0;
            for ($j = 0; $j < $numBoxes; ++$j) {
                $logit = $logits[$j];
                $indices = [];
                $probs = [];
                if ($isZeroShot) {
                    $logitSigmoid = $logit->sigmoid();
                    foreach ($logitSigmoid as $k => $prob) {
                        if ($prob > $threshold) {
                            $indices[] = $k;
                        }
                    }
                    $probs = $logitSigmoid;
                } else {
                    $maxIndex = $logit->argMax();
                    if ($maxIndex === $numClasses - 1) {
                        continue;
                    }
                    $indices[] = $maxIndex;
                    $probs = $logit->softmax();
                }
                foreach ($indices as $index) {
                    $box = $bbox[$j]->toArray();
                    // convert to [x0, y0, x1, y1] format
                    $box = self::centerToCornersFormat($box);
                    if ($targetSize !== null) {
                        $box = array_map(fn($x, $i) => $x * $targetSize[($i + 1) % 2], $box, array_keys($box));
                    }

                    $info['boxes'][] = $box;
                    $info['classes'][] = $index;
                    $info['scores'][] = $probs[$index];
                    $detectionCount++;
                }
            }
            $logger->info('Object detection post-processing complete for batch item', [
                'item' => $i,
                'detections' => $detectionCount
            ]);
            $toReturn[] = $info;
        }
        return $toReturn;
    }

    /**
     * Converts bounding boxes from center format to corners format.
     *
     * @param array $arr The coordinates for the center of the box and its width, height dimensions (center_x, center_y, width, height).
     * @return array The coordinates for the top-left and bottom-right corners of the box (top_left_x, top_left_y, bottom_right_x, bottom_right_y).
     */
    public static function centerToCornersFormat(array $arr): array
    {
        [$centerX, $centerY, $width, $height] = $arr;

        $clampFn = fn(float $value, float $min, float $max) => max($min, min($max, $value));

        $topLeftX = $clampFn($centerX - $width / 2, 0.0, 1.0);
        $topLeftY = $clampFn($centerY - $height / 2, 0.0, 1.0);
        $bottomRightX = $clampFn($centerX + $width / 2, 0.0, 1.0);
        $bottomRightY = $clampFn($centerY + $height / 2, 0.0, 1.0);

        return [$topLeftX, $topLeftY, $bottomRightX, $bottomRightY];
    }

    public function __invoke(mixed $input, ...$args)
    {
        return $this->featureExtractor->__invoke($input, ...$args);
    }
}
