<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Audio;
use function Codewithkyrian\Transformers\Utils\timeUsage;

class ASTFeatureExtractor extends FeatureExtractor
{
    protected array $melFilters;
    protected Tensor $window;
    protected mixed $mean;
    protected mixed $std;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $samplingRate = $config['sampling_rate'];

        $this->melFilters = Audio::melFilterBank(
            256,
            $config['num_mel_bins'],
            20,
            floor($samplingRate / 2),
            $samplingRate,
            null,
            "kaldi",
            true
        );

        // Do padding:
        for ($i = 0; $i < count($this->melFilters); $i++) {
            $this->melFilters[$i][] = 0;
        }

        $this->window = Audio::windowFunction(400, 'hann', false);

        $this->mean = $config['mean'];
        $this->std = $config['std'];
    }

    /**
     *  Extracts features from a given audio using the provided configuration.
     * @param Tensor $input The audio tensor to extract features from.
     * @return Tensor[] The extracted features.
     */
    public function __invoke($input, ...$args): array
    {
        $features = Audio::spectrogram(
            $input,
            $this->window,
            frameLength: 400,
            hopLength: 160,
            fftLength: 512,
            power: 2.0,
            center: false,
            preemphasis: 0.97,
            melFilters: $this->melFilters,
            melFloor: 1.192092955078125e-07,
            logMel: 'log',
            removeDcOffset: true,
            maxNumFrames: $this->config['max_length'],
            transpose: true
        );

        return [
            'input_values' => $features->add(-$this->mean)->multiply(1 / $this->std)->unsqueeze(0)
        ];
    }
}
