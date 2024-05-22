<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Audio;
use function Codewithkyrian\Transformers\Utils\timeUsage;

class WhisperFeatureExtractor extends FeatureExtractor
{
    protected Tensor $window;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->config['mel_filters'] ??= Audio::melFilterBank(
            (int)(1 + $config['n_fft'] / 2),
            nMelFilters: $config['feature_size'],
            minFrequency: 0,
            maxFrequency: 8000,
            samplingRate: $config['sampling_rate'],
            norm: 'slaney',
            melScale: 'slaney',
        );

        $this->window = Audio::windowFunction($config['n_fft'], 'hann', false);
    }

    /**
     *  Extracts features from a given audio using the provided configuration.
     * @param Tensor $waveform The audio tensor to extract features from.
     * @return Tensor[] The extracted features.
     */
    public function __invoke(Tensor $waveform): array
    {
        if ($waveform->size() > $this->config['n_samples']) {
            trigger_error('Attempting to extract features for audio longer than 30 seconds.' .
                'If using a pipeline to extract transcript from a long audio clip,' .
                'remember to specify `chunkLengthSecs` and/or `strideLengthSecs` in the pipeline options.', E_USER_WARNING);

            $waveform = $waveform->slice(0, $this->config['n_samples']);
        } else {
            $padding = $this->config['n_samples'] - $waveform->size();
            // create a new Tensor with the same data type as the input waveform
            $padding = Tensor::zeros([$padding], dtype: $waveform->dtype());
            $waveform = Tensor::concat([$waveform, $padding]);
        }

        timeUsage();
        $features = Audio::spectrogram(
            $waveform,
            $this->window,
            frameLength: $this->config['n_fft'],
            hopLength: $this->config['hop_length'],
            power: 2.0,
            melFilters: $this->config['mel_filters'],
            logMel: 'log10',

            maxNumFrames: $this->config['nb_max_frames'],
        );

        $maxValue = $features->max();

        $features->u(fn($x) => (max($x, $maxValue - 8.0) + 4.0) / 4.0);

        return [
            'input_features' => $features->unsqueeze(0)
        ];
    }
}