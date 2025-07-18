<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Audio;
use Codewithkyrian\Transformers\Transformers;

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
            $logger = Transformers::getLogger();
            $logger->warning('Attempting to extract features for audio longer than 30 seconds.' .
                'If using a pipeline to extract transcript from a long audio clip,' .
                'remember to specify `chunkLengthSecs` and/or `strideLengthSecs` in the pipeline options.');

            $waveform = $waveform->sliceWithBounds([0], [$this->config['n_samples']]);
        } else if ($waveform->size() < $this->config['n_samples']) {
            $padLength = $this->config['n_samples'] - $waveform->size();
            $padding = Tensor::zeros([$padLength], dtype: $waveform->dtype());
            $waveform = Tensor::concat([$waveform, $padding]);
        }

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

        $features = $features
            ->maximum($maxValue - 8.0)
            ->add(4.0)
            ->multiply(1.0 / 4.0);

        return [
            'input_features' => $features->unsqueeze(0)
        ];
    }
}
