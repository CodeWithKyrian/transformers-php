<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Utils\Audio;

use function Codewithkyrian\Transformers\Utils\array_pop_key;

/**
 * Audio classification pipeline using any `AutoModelForAudioClassification`.
 * This pipeline predicts the class of a raw waveform or an audio file.
 *
 * *Example:** Perform audio classification with `Xenova/wav2vec2-large-xlsr-53-gender-recognition-librispeech`.
 *  ```php
 *  $classifier = pipeline('audio-classification', 'Xenova/wav2vec2-large-xlsr-53-gender-recognition-librispeech');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/jfk.wav';
 *  $output = $classifier($url);
 *  // [
 *  //   [ label: 'male', score: 0.9981542229652405 ],
 *  //   [ label: 'female', score: 0.001845747814513743 ]
 *  // ]
 *  ```
 *
 * *Example:** Perform audio classification with `Xenova/ast-finetuned-audioset-10-10-0.4593` and return top 4 results.
 *  ```php
 *  $classifier = await pipeline('audio-classification', 'Xenova/ast-finetuned-audioset-10-10-0.4593');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/cat_meow.wav';
 *  $output = $classifier($url, topK: 4);
 *  // [
 *  //   [ label: 'Meow', score: 0.5617874264717102 ],
 *  //   [ label: 'Cat', score: 0.22365376353263855 ],
 *  //   [ label: 'Domestic animals, pets', score: 0.1141069084405899 ],
 *  //   [ label: 'Animal', score: 0.08985692262649536 ],
 *  // ]
 *  ```
 */
class AudioClassificationPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array
    {
        $topK = array_pop_key($args, 'topK', 1);

        $isBatched = is_array($inputs);

        if (!$isBatched) {
            $inputs = [$inputs];
        }

        $sampleRate = $this->processor->featureExtractor->config['sampling_rate'];
        $id2label = $this->model->config['id2label'];
        $toReturn = [];

        foreach ($inputs as $input) {
            $audio = new Audio($input);
            $audioTensor = $audio->toTensor(samplerate: $sampleRate);

            $processedInputs = ($this->processor)($audioTensor);
            $outputs = ($this->model)($processedInputs);

            $logits = $outputs['logits'][0];

            [$scores, $indices] = $logits->softmax()->topk($topK, true);

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

        return $isBatched || $topK === 1 ? $toReturn : $toReturn[0];
    }
}