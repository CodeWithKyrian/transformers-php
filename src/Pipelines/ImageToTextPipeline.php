<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\GenerationConfig;
use function Codewithkyrian\Transformers\Utils\camelCaseToSnakeCase;
use function Codewithkyrian\Transformers\Utils\prepareImages;

/**
 * Image To Text pipeline using a `AutoModelForVision2Seq`. This pipeline predicts a caption for a given image.
 *
 * *Example:** Generate a caption for an image w/ `Xenova/vit-gpt2-image-captioning`.
 *  ```php
 *  $captioner = pipeline('image-to-text', 'Xenova/vit-gpt2-image-captioning');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/cats.jpg';
 *  $output = $captioner($url);
 *  // [{ generated_text: 'a cat laying on a couch with another cat' }]
 *  ```
 *
 *  **Example:** Optical Character Recognition (OCR) w/ `Xenova/trocr-small-handwritten`.
 *  ```php
 *  $captioner = await pipeline('image-to-text', 'Xenova/trocr-small-handwritten');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/handwriting.jpg';
 *  $output = $captioner($url);
 *  // [{ generated_text: 'Mr. Brown commented icily.' }]
 *  ```
 */
class ImageToTextPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $streamer = null;

        if (array_key_exists('streamer', $args)) {
            $streamer = $args['streamer'];
            unset($args['streamer']);
        }

        // Convert the rest of the arguments key names from camelCase to snake_case
        $snakeCasedArgs = [];

        foreach ($args as $key => $value) {
            $snakeCasedArgs[camelCaseToSnakeCase($key)] = $value;
        }

        $generationConfig = new GenerationConfig($snakeCasedArgs);

        $isBatched = is_array($inputs);

        $preparedImages = prepareImages($inputs);

        ['pixel_values' => $pixelValues] = ($this->processor)($preparedImages);

        $toReturn = [];

        foreach ($pixelValues as $batch) {
            $batch = $batch->reshape([1, ...$batch->shape()]);

            $output = $this->model->generate($batch, generationConfig: $generationConfig, streamer: $streamer);

            $decoded = array_map(
                fn($x) => ['generated_text' => trim($x)],
                $this->tokenizer->batchDecode($output, skipSpecialTokens: true)
            );

            $toReturn[] = $decoded;
        }

        return $isBatched ? $toReturn : $toReturn[0];
    }

}