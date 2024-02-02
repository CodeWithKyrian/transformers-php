<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

/**
 * A Normalizer that applies a sequence of Normalizers.
 */
class NormalizerSequence extends Normalizer
{
    /**
     * @var Normalizer[]
     */
    protected array $normalizers;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->normalizers = array_map(
            fn (array $config) => Normalizer::fromConfig($config),
            $config['normalizers']
        );
    }

    public function normalize(string $text): string
    {
        return array_reduce(
            $this->normalizers,
            fn (string $text, Normalizer $normalizer) => $normalizer->normalize($text),
            $text
        );
    }
}