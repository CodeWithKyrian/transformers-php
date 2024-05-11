<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

use Codewithkyrian\Transformers\Models\Output\ObjectDetectionOutput;
use Codewithkyrian\Transformers\Processors\Processor;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Image;
use Interop\Polite\Math\Matrix\NDArray;

class DetrFeatureExtractor extends ImageFeatureExtractor
{
    /**
     * Calls the feature extraction process on an array of images, preprocesses
     * each image, and concatenates the resulting features into a single Tensor.
     * @param Image|array $images The image(s) to extract features from.
     * @return array An object containing the concatenated pixel values of the preprocessed images.
     */
    public function __invoke(Image|array $images, ...$args): array
    {
        $result = parent::__invoke($images, $args);


        // TODO support differently-sized images, for now assume all images are the same size.
        // TODO support different mask sizes (not just 64x64)
        // Currently, just fill pixel mask with 1s
        $maskSize = [$result['pixel_values']->shape()[0], 64, 64];

        $pixelMaskData = array_fill(0, array_product($maskSize), 1);

        $pixelMask = new Tensor($pixelMaskData, NDArray::int64, $maskSize);

        return ['pixel_values' => $result['pixel_values'], 'pixel_mask' => $pixelMask];
    }


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