<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Output\SequenceClassifierOutput;
use Codewithkyrian\Transformers\Utils\Math;

use function Codewithkyrian\Transformers\Utils\array_pop_key;
use function Codewithkyrian\Transformers\Utils\prepareImages;
use function Codewithkyrian\Transformers\Utils\timeUsage;

/**
 * Image classification pipeline using any `AutoModelForImageClassification`.
 *  This pipeline predicts the class of an image.
 *
 * *Example:** Classify an image.
 *  ```php
 *  $classifier =  pipeline('image-classification', 'Xenova/vit-base-patch16-224');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/tiger.jpg';
 *  $output =  $classifier($url);
 *  // [
 *  //   { label: 'tiger, Panthera tigris', score: 0.632695734500885 },
 *  // ]
 *  ```
 *
 * *Example:** Classify an image and return top `n` classes.
 *  ```php
 *  $classifier = pipeline('image-classification', 'Xenova/vit-base-patch16-224');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/tiger.jpg';
 *  $output = $classifier($url, topK: 3);
 *  // [
 *  //   { label: 'tiger, Panthera tigris', score: 0.632695734500885 },
 *  //   { label: 'tiger cat', score: 0.3634825646877289 },
 *  //   { label: 'lion, king of beasts, Panthera leo', score: 0.00045060308184474707 },
 *  // ]
 *  ```
 *
 * *Example:** Classify an image and return all classes.
 * ```php
 *   $classifier = pipeline('image-classification', 'Xenova/vit-base-patch16-224');
 *   $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/tiger.jpg';
 *   $output = $classifier($url, topK: 0);
 *  // [
 *  //   { label: 'tiger, Panthera tigris', score: 0.632695734500885 },
 *  //   { label: 'tiger cat', score: 0.3634825646877289 },
 *  //   { label: 'lion, king of beasts, Panthera leo', score: 0.00045060308184474707 },
 *  //   { label: 'jaguar, panther, Panthera onca, Felis onca', score: 0.00035465499968267977 },
 *  //   ...
 *  // ]
 *  ```
 */
class ImageClassificationPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $topK = array_pop_key($args, 'topK', 1);

        $isBatched = is_array($inputs);

        $preparedImages = prepareImages($inputs);

        ['pixel_values' => $pixelValues] = ($this->processor)($preparedImages);

        /** @var SequenceClassifierOutput $output */
        $output = $this->model->__invoke(['pixel_values' => $pixelValues]);

        $id2label = $this->model->config['id2label'];

        $toReturn = [];

        foreach ($output->logits as $batch) {
            [$scores, $indices] = $batch->softmax()->topk($topK);

            $values = [];

            foreach ($indices as $i => $index) {
                $values[] = ['label' => $id2label[$index], 'score' => $scores[$i]];
            }


            if ($topK === 1) {
                $toReturn = array_merge($toReturn, $values);
            } else {
                $toReturn[] = $values;
            }
        }

        if ($isBatched || $topK === 1) {
            return $toReturn;
        } else {
            return $toReturn[0];
        }
    }
}