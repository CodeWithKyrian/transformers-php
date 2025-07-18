<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

use Codewithkyrian\Transformers\Transformers;

/**
 * A base class for text normalization.
 */
abstract class Normalizer
{
    public function __construct(protected array $config) {}

    public static function fromConfig(?array $config): ?self
    {
        if ($config === null) {
            return null;
        }

        $logger = Transformers::getLogger();
        $logger->debug('Creating normalizer', ['type' => $config['type'] ?? 'unknown']);

        return match ($config['type'] ?? null) {
            'BertNormalizer' => new BertNormalizer($config),
            'Precompiled' => new Precompiled($config),
            'Sequence' => new NormalizerSequence($config),
            'Replace' => new Replace($config),
            'NFC' => new NFC($config),
            'NFKC' => new NFKC($config),
            'NFKD' => new NFKD($config),
            'Strip' => new StripNormalizer($config),
            'StripAccents' => new StripAccents($config),
            'Lowercase' => new Lowercase($config),
            'Prepend' => new Prepend($config),
            default => throw new \InvalidArgumentException('Unknown normalizer type: ' . $config['type'] ?? null),
        };
    }

    abstract public function normalize(string $text): string;

    public function __invoke(): string
    {
        return $this->normalize(...func_get_args());
    }
}
