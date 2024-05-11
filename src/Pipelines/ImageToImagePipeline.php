<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Image;
use Exception;
use Interop\Polite\Math\Matrix\NDArray;
use function Codewithkyrian\Transformers\Utils\prepareImages;

/**
 * Image to Image pipeline using any `AutoModelForImageToImage`. This pipeline generates an image based on a previous image input.
 *
 * **Example:** Super-resolution w/ `Xenova/swin2SR-classical-sr-x2-64`
 * ```php
 * $upscaler = pipeline('image-to-image', 'Xenova/swin2SR-classical-sr-x2-64');
 * $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/butterfly.jpg';
 * $output = $upscaler($url, saveTo: 'butterfly-super-resolution.jpg');
 * // [
 * //   path: 'butterfly-super-resolution.jpg',
 * //   width: 512,
 * //   height: 512,
 * //   channels: 3
 * // ]
 * ```
 */
class ImageToImagePipeline extends Pipeline
{

    /**
     * @param array|string $inputs
     * @param mixed ...$args
     * @return Image|Image[]
     * @throws Exception
     */
    public function __invoke(array|string $inputs, ...$args): array|Image
    {
        $saveTo = $args[0] ?? $args['saveTo'] ?? null;

        if (!$saveTo) {
            throw new Exception('You must provide a save path');
        }

        if (!is_array($saveTo)) {
            $saveTo = [$saveTo];
        }

        $preparedImages = prepareImages($inputs);

        $inputs = ($this->processor)($preparedImages);

        $outputs = $this->model->__invoke($inputs);

        $toReturn = [];

        /** @var Tensor $batch */
        foreach ($outputs['reconstruction'] as $i => $batch) {
            $output = $batch->squeeze()
                ->clamp(0, 1)
                ->multiply(255)
                ->round()
                ->to(NDArray::uint8);

            $image = Image::fromTensor($output);

            $image->save($saveTo[$i]);

            $toReturn[] = [
                'path' => $saveTo[$i],
                'width' => $image->width(),
                'height' => $image->height(),
                'channels' => $image->channels,
            ];
        }

        return count($toReturn) > 1 ? $toReturn : $toReturn[0];
    }
}
