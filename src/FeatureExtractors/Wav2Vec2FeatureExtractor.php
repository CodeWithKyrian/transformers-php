<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

use Codewithkyrian\Transformers\Tensor\Tensor;
use function Codewithkyrian\Transformers\Utils\timeUsage;

class Wav2Vec2FeatureExtractor extends FeatureExtractor
{
    /**
     *  Extracts features from a given audio using the provided configuration.
     * @param Tensor $waveform The audio tensor to extract features from.
     * @return Tensor[] The extracted features.
     */
    public function __invoke(Tensor $waveform): array
    {
        // zero-mean and unit-variance normalization
        if ($this->config['do_normalize'])
        {
            $mean = $waveform->mean();

            //calculate the variance
//            $variance = $waveform->add(-$mean)->pow(2)->mean();
            $variance = 0;
            for ($i = 0; $i < $waveform->size(); $i++) {
                $variance += pow($waveform[$i] - $mean, 2);
            }
            $variance /= $waveform->size();

            //normalize the waveform
            $waveform = $waveform->add(-$mean)->multiply(1.0 / sqrt($variance + 1e-7));
        }

        $shape = [1, $waveform->size()];

        return [
            'input_values' => $waveform->reshape($shape),
            'attention_mask' => Tensor::ones($shape, dtype: Tensor::int64)
        ];
    }
}