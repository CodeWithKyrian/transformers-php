<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

class Swin2SRImageProcessor extends ImageFeatureExtractor
{
    public function padImage(
        array     $pixelData,
        array     $imgShape,
        int|array $padSize,
        string    $mode = 'constant',
        bool      $center = false,
        int       $constantValues = 0
    ): array
    {
        // NOTE: In this case, `padSize` represents the size of the sliding window for the local attention.
        // In other words, the image is padded so that its width and height are multiples of `padSize`.
        [$imageHeight, $imageWidth, $imageChannels] = $imgShape;

        // NOTE: For Swin2SR models, the original python implementation adds padding even when the image's width/height is already
        // a multiple of `pad_size`. However, this is most likely a bug (PR: https://github.com/mv-lab/swin2sr/pull/19).
        // For this reason, we only add padding when the image's width/height is not a multiple of `pad_size`.
        $padSize = [
            'width' => $imageWidth + ($padSize - $imageWidth % $padSize) % $padSize,
            'height' => $imageHeight + ($padSize - $imageHeight % $padSize) % $padSize,
        ];

        return parent::padImage($pixelData, $imgShape, $padSize, 'symmetric', false, -1);
    }
}