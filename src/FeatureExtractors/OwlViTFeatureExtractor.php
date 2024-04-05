<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

use Codewithkyrian\Transformers\Models\Output\ObjectDetectionOutput;
use Codewithkyrian\Transformers\Processors\Processor;

class OwlViTFeatureExtractor extends ImageFeatureExtractor
{
    /**
     * Post-processes the outputs of the model (for object detection).
     * @param ObjectDetectionOutput $outputs The outputs of the model that must be post-processed
     * @param float $threshold The threshold to use for the scores.
     * @param array|null $targetSizes The sizes of the original images.
     * @param bool $isZeroShot Whether zero-shot object detection was performed.
     * @return array An array of objects containing the post-processed outputs.
     */
    public function postProcessObjectDetection(ObjectDetectionOutput $outputs, float $threshold = 0.5, ?array $targetSizes = null, bool $isZeroShot = false): array
    {
        return Processor::postProcessObjectDetection($outputs, $threshold, $targetSizes, $isZeroShot);
    }
}