<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Audio;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Image;
use function Codewithkyrian\Transformers\Utils\array_pop_key;
use function Codewithkyrian\Transformers\Utils\camelCaseToSnakeCase;
use function Codewithkyrian\Transformers\Utils\timeUsage;

/**
 * Pipeline that aims at extracting spoken text contained within some audio.
 *
 * *Example:** Transcribe English.
 *  ```php
 *  $transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-tiny.en');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/jfk.wav';
 *  $output = $transcriber($url);
 *  // [ text: " And so my fellow Americans ask not what your country can do for you, ask what you can do for your country." ]
 *  ```
 *
 *  **Example:** Transcribe English w/ timestamps.
 *  ```php
 *  $transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-tiny.en');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/jfk.wav';
 *  $output = $transcriber($url, returnTimestamps: true);
 *  // [
 *  //   text: " And so my fellow Americans ask not what your country can do for you, ask what you can do for your country."
 *  //   chunks: [
 *  //     [ timestamp: [0, 8],  text: " And so my fellow Americans ask not what your country can do for you" ],
 *  //     [ timestamp: [8, 11], text: " ask what you can do for your country." ],
 *  //   ]
 *  // ]
 *  ```
 *
 *  **Example:** Transcribe English w/ word-level timestamps.
 *  ```php
 *  $transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-tiny.en');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/jfk.wav';
 *  $output = $transcriber($url, returnTimestamps: 'word');
 *  // [
 *  //   "text": " And so my fellow Americans ask not what your country can do for you ask what you can do for your country.",
 *  //   "chunks": [
 *  //     [ "text": " And", "timestamp": [0, 0.78] ],
 *  //     [ "text": " so", "timestamp": [0.78, 1.06] ],
 *  //     [ "text": " my", "timestamp": [1.06, 1.46] ],
 *  //     ...
 *  //     [ "text": " for", "timestamp": [9.72, 9.92] ],
 *  //     [ "text": " your", "timestamp": [9.92, 10.22] ],
 *  //     [ "text": " country.", "timestamp": [10.22, 13.5] ]
 *  //   ]
 *  // ]
 *  ```
 *
 *  **Example:** Transcribe French.
 *  ```php
 *  $transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-small');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/french-audio.mp3';
 *  $output = $transcriber($url, language: 'french', task: 'transcribe');
 *  // [ text: " J'adore, j'aime, je n'aime pas, je déteste." ]
 *  ```
 *
 *  **Example:** Translate French to English.
 *  ```php
 *  $transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-small');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/french-audio.mp3';
 *  $output = $transcriber($url, language: 'french', task: 'translate');
 *  // [ text: " I love, I like, I don't like, I hate." ]
 *  ```
 *
 *  **Example:** Transcribe/translate audio longer than 30 seconds.
 *  ```php
 *  $transcriber = await pipeline('automatic-speech-recognition', 'Xenova/whisper-tiny.en');
 *  $url = 'https://huggingface.co/datasets/Xenova/transformers.js-docs/resolve/main/ted_60.wav';
 *  $output = $transcriber($url, chunkLengthSecs: 30, strideLengthSecs: 5);
 *  // [ text: " So in college, I was a government major, which means [...] So I'd start off light and I'd bump it up" ]
 *  ```
 */
class AutomaticSpeechRecognitionPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): array|Tensor|Image
    {
        return match ($this->model->config->modelType) {
            'whisper' => $this->__invokeWhisper($inputs, ...$args),
            'wav2vec2',
            'wav2vec2-bert',
            'unispeech',
            'unispeech-sat',
            'hubert' => $this->__invokeWav2Vec2($inputs, ...$args),
            default => throw new \InvalidArgumentException("Model type {$this->model->config->modelType} not supported for Automatic Speech Recognition"),
        };
    }

    private function __invokeWhisper(array|string $inputs, ...$args): array|Tensor|Image
    {
        $returnTimestamps = $args['returnTimestamps'] ?? false;
        $chunkLengthSecs = $args['chunkLengthSecs'] ?? 0;
        $chunkCallback = $args['chunkCallback'] ?? null;
        $forceFullSequences = $args['forceFullSequences'] ?? false;
        $strideLengthSecs = $args['strideLengthSecs'] ?? null;

        if ($returnTimestamps === 'word') {
            $args['return_token_timestamps'] = true;
        }

        $language = array_pop_key($args, 'language');
        $task = array_pop_key($args, 'task');
        $streamer = array_pop_key($args, 'streamer');

        // Convert the rest of the arguments key names from camelCase to snake_case
        $snakeCasedArgs = [];
        foreach ($args as $key => $value) {
            $snakeCasedArgs[camelCaseToSnakeCase($key)] = $value;
        }

        $generationConfig = new GenerationConfig($snakeCasedArgs);

        if ($language || $task || $returnTimestamps) {
            if (isset($args['forcedDecoderIds'])) {
                throw new \InvalidArgumentException('Cannot specify `forcedDecoderIds` when specifying `language`, `task`, or `returnTimestamps`');
            }

            $decoderPromptIds = $this->tokenizer->getDecoderPromptIds(language: $language, task: $task, noTimestamps: !$returnTimestamps);

            if (count($decoderPromptIds) > 0) {
                $args['forcedDecoderIds'] = $decoderPromptIds;
            }
        }

        $isBatched = is_array($inputs);
        if (!$isBatched) {
            $inputs = [$inputs];
        }

        $timePrecision = $this->processor->featureExtractor->config['chunk_length'] / $this->model->config['max_source_positions'];
        $hopLength = $this->processor->featureExtractor->config['hop_length'];

        $samplingRate = $this->processor->featureExtractor->config['sampling_rate'];

        $toReturn = [];

        foreach ($inputs as $input) {
            $audio = Audio::read($input);
            $audioTensor = $audio->toTensor(samplerate: $samplingRate);

            $chunks = [];

            if ($chunkLengthSecs > 0) {

                if ($strideLengthSecs === null) {
                    $strideLengthSecs = $chunkLengthSecs / 6;
                } elseif ($chunkLengthSecs <= $strideLengthSecs) {
                    throw new \InvalidArgumentException('`strideLengthSecs` must be less than `chunkLengthSecs`');
                }

                $window = $chunkLengthSecs * $samplingRate;
                $stride = $strideLengthSecs * $samplingRate;
                $jump = $window - 2 * $stride;
                $offset = 0;


                while ($offset < $audioTensor->size()) {

                    if ($offset + $window > $audioTensor->size()) {
                        $window = $audioTensor->size() - $offset;
                    }

                    $subAudio = $audioTensor->sliceWithBounds([$offset], [$window]);
                    $feature = ($this->processor)($subAudio);

                    $isFirstChunk = $offset === 0;
                    $isLastChunk = $offset + $jump >= $audioTensor->size();

                    $chunks[] = [
                        'stride' => [
                            $subAudio->size(),
                            $isFirstChunk ? 0 : $stride,
                            $isLastChunk ? 0 : $stride
                        ],
                        'input_features' => $feature['input_features'],
                        'is_last' => $isLastChunk
                    ];

                    $offset += $jump;
                }
            } else {
                $chunks = [
                    [
                        'stride' => [$audioTensor->size(), 0, 0],
                        'input_features' => ($this->processor)($audioTensor)['input_features'],
                        'is_last' => true
                    ]
                ];

            }

            // Generate for each set of input features
            foreach ($chunks as &$chunk) {
                $generationConfig['num_frames'] = floor($chunk['stride'][0] / $hopLength);

                $streamer?->init($this->tokenizer, []);
                $data = $this->model->generate($chunk['input_features'], generationConfig: $generationConfig, streamer: $streamer);

                // TODO: Right now we only get top beam
                if ($returnTimestamps === 'word') {
                    $chunk['tokens'] = $data['sequences'][0];
                    $chunk['token_timestamps'] = $data['token_timestamps'][0]->round(2);
                } else {
                    $chunk['tokens'] = $data[0];
                }

                // convert stride to seconds
                $chunk['stride'] = array_map(fn($x) => $x / $samplingRate, $chunk['stride']);

                if ($chunkCallback) {
                    $chunkCallback($chunk);
                }
            }

            // Merge text chunks
            [$fullText, $optional] = $this->tokenizer->decodeASR($chunks,
                timePrecision: $timePrecision,
                returnTimestamps: $returnTimestamps,
                forceFullSequences: $forceFullSequences
            );

            $toReturn[] = ['text' => $fullText, ...$optional];
        }

        return $isBatched ? $toReturn : $toReturn[0];
    }

    private function __invokeWav2Vec2(array|string $inputs, ...$args): array|Tensor|Image
    {
        throw new \Error('Not implemented');
    }


}