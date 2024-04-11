<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\Tensor;
use function Codewithkyrian\Transformers\Utils\prepareImages;

/**
 * Zero shot image classification pipeline. This pipeline predicts the class of
 *  an image when you provide an image and a set of `candidateLabels`.
 *
 * *Example:** Zero shot image classification w/ `Xenova/clip-vit-base-patch32`.
 *  ```php
 *  $classifier = pipeline('zero-shot-image-classification', 'Xenova/clip-vit-base-patch32');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/tiger.jpg';
 *  $output = $classifier($url, ['tiger', 'horse', 'dog']);
 *  // [
 *  //   { score: 0.9950892734865646, label: 'tiger' },
 *  //   { score: 0.0032317680452433, label: 'horse' },
 *  //   { score: 0.0016789584681969, label: 'dog' }
 *  // ]
 *  ```
 */
class ZeroShotImageClassificationPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $candidateLabels = $args[0];
        $hypothesisTemplate = $args['hypothesisTemplate'] ?? "This is a photo of {}";

        $isBatched = is_array($inputs);
        $preparedImages = prepareImages($inputs);

        // Insert label into hypothesis template
        $texts = array_map(fn($x) => str_replace('{}', $x, $hypothesisTemplate), $candidateLabels);

        // Run tokenization
        $textInputs = $this->tokenizer->tokenize($texts,
            padding: $this->model->config['model_type'] === 'siglip' ? 'max_length' : true,
            truncation: true,
        );


        // Run processor
        ['pixel_values' => $pixelValues] = ($this->processor)($preparedImages);

        // Run model with both text and pixel inputs
        $output = $this->model->__invoke(array_merge($textInputs, ['pixel_values' => $pixelValues]));

        $activationFn = $this->model->config['model_type'] === 'siglip' ?
            fn(Tensor $batch) => $batch->sigmoid()->toArray() :
            fn(Tensor $batch) => $batch->softmax();

        // Compare each image with each candidate label
        $toReturn = [];

        foreach ($output['logits_per_image'] as $batch) {
            // Compute softmax per image
            $scores = $activationFn($batch);

            $result = [];
            foreach ($scores as $i => $score) {
                $result[] = [
                    'score' => $score,
                    'label' => $candidateLabels[$i]
                ];
            }
            usort($result, fn($a, $b) => $b['score'] <=> $a['score']); // sort by score in descending order
            $toReturn[] = $result;
        }

        return $isBatched ? $toReturn : $toReturn[0];
    }

}