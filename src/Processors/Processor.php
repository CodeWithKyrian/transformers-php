<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Processors;

use Codewithkyrian\Transformers\FeatureExtractors\FeatureExtractor;

/**
 * Represents a Processor that extracts features from an input.
 */
class Processor
{
    public function __construct(
        public FeatureExtractor $featureExtractor
    )
    {
    }

    public function __invoke(mixed $input, ...$args)
    {
        return $this->featureExtractor->__invoke($input, ...$args);
    }
}