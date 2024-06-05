<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

/**
 * The bare Wav2Vec2 Model transformer outputting raw hidden-states without any specific head on top.
 *
 * **Example:** Load and run a `Wav2Vec2Model` for feature extraction.
 *
 * ```php
 *
 * // Read and preprocess audio
 * $processor = AutoProcessor::fromPretrained('Xenova/mms-300m');
 * $audio =  Audio::read('https://huggingface.co/datasets/Narsil/asr_dummy/resolve/main/mlk.flac');
 * $audioTensor = $audio->toTensor(samplerate: 16000);
 * $inputs = $processor($audioTensor);
 *
 * // Run model with inputs
 * $model = AutoModel::from_pretrained('Xenova/mms-300m');
 * $output = $model($inputs);
 * // {
 * //   last_hidden_state: Tensor {
 * //     shape: [ 1, 1144, 1024 ],
 * //     dtype: 'float32',
 * //     buffer: (1171456) [ ... ],
 * //     size: 1171456
 * //   }
 * // }
 * ```
 */
class Wav2Vec2Model extends Wav2Vec2PretrainedModel
{

}