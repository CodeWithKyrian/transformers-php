<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Processors;

use Codewithkyrian\Transformers\FeatureExtractors\FeatureExtractor;
use Codewithkyrian\Transformers\Models\Output\ObjectDetectionOutput;
use Codewithkyrian\Transformers\Utils\Math;
use Exception;

/**
 * Represents a Processor that extracts features from an input.
 */
class Processor
{
    public function __construct(
        public FeatureExtractor $featureExtractor
    )
    {
    }

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

        $outLogits = $outputs->logits;
        $outBbox = $outputs->predBoxes;

        [$batchSize, $numBoxes, $numClasses] = $outLogits->shape();


        if ($targetSizes !== null && count($targetSizes) !== $batchSize) {
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

            for ($j = 0; $j < $numBoxes; ++$j) {
                $logit = $logits[$j];

                $indices = [];
                $probs = [];
                if ($isZeroShot) {
                    // Get indices of classes with high enough probability
                    $logitSigmoid = Math::sigmoid($logit->toArray());
                    foreach ($logitSigmoid as $k => $prob) {
                        if ($prob > $threshold) {
                            $indices[] = $k;
                        }
                    }
                    $probs = $logitSigmoid;
                } else {
                    // Get most probable class
                    $maxIndex = $logit->argMax();

                    if ($maxIndex === $numClasses - 1) {
                        // This is the background class, skip it
                        continue;
                    }
                    $indices[] = $maxIndex;

                    // Compute softmax over classes
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
                }

            }
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

        $topLeftX = Math::clamp($centerX - $width / 2, 0.0, 1.0);
        $topLeftY = Math::clamp($centerY - $height / 2, 0.0, 1.0);
        $bottomRightX = Math::clamp($centerX + $width / 2, 0.0, 1.0);
        $bottomRightY = Math::clamp($centerY + $height / 2, 0.0, 1.0);

        return [$topLeftX, $topLeftY, $bottomRightX, $bottomRightY];
    }

    public function __invoke(mixed $input, ...$args)
    {
        return $this->featureExtractor->__invoke($input, ...$args);
    }

}