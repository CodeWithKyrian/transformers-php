<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

/**
 * Base class for feature extractors.
 */
class FeatureExtractor
{
    public function __construct(public array $config) {}

    public function __invoke(mixed $input, ...$args)
    {
        return $input;
    }
}
