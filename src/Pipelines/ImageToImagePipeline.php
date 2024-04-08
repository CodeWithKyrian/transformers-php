<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\Tensor;
use Interop\Polite\Math\Matrix\NDArray;
use function Codewithkyrian\Transformers\Utils\prepareImages;
use function Codewithkyrian\Transformers\Utils\timeUsage;

/**
 * Image to Image pipeline using any `AutoModelForImageToImage`. This pipeline generates an image based on a previous image input.
 *
 * **Example:** Super-resolution w/ `Xenova/swin2SR-classical-sr-x2-64`
 * ```php
 * $upscaler = pipeline('image-to-image', 'Xenova/swin2SR-classical-sr-x2-64');
 * $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/butterfly.jpg';
 * $output = $upscaler($url);
 * // Image {
 * //   data: array(786432) [ 41, 31, 24,  43, ... ],
 * //   width: 512,
 * //   height: 512,
 * //   channels: 3
 * // }
 * ```
 */
class ImageToImagePipeline extends Pipeline
{

    /**
     * @param array|string $inputs
     * @param mixed ...$args
     * @return Image|Image[]
     * @throws \Exception
     */
    public function __invoke(array|string $inputs, ...$args): array|Image
    {
        $preparedImages = prepareImages($inputs);

        $inputs = ($this->processor)($preparedImages);

        $outputs = $this->model->__invoke($inputs);

        $toReturn = [];

        /** @var Tensor $batch */
        foreach ($outputs['reconstruction'] as $batch) {
            $output = $batch->squeeze()
                ->clamp(0, 1)
                ->multiplyScalar(255)
                ->round()
                ->to(NDArray::uint8);

            $toReturn[] = Image::fromTensor($output);
        }

        return count($toReturn) > 1 ? $toReturn : $toReturn[0];
    }
}
