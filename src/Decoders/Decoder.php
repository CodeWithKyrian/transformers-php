<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

use Codewithkyrian\Transformers\Tokenizers\AddedToken;

/**
 * The base class for token decoders.
 */
abstract class Decoder
{
    /**
     * @var AddedToken[]
     */
    public array $addedTokens = [];

    public ?string $endOfWordSuffix = null;

    protected bool $trimOffsets;

    public function __construct(protected array $config)
    {
        $this->trimOffsets = $config['trim_offsets'] ?? false;
    }


    /**
     * Creates a decoder instance based on the provided configuration.
     *
     * @param ?array $config
     * @return ?self
     */
    public static function fromConfig(?array $config): ?self
    {
        if ($config === null) {
            return null;
        }

        return match ($config['type']) {
            'WordPiece' => new WordPieceDecoder($config),
            'Metaspace' => new MetaspaceDecoder($config),
            'ByteLevel' => new ByteLevelDecoder($config),
            'Replace' => new ReplaceDecoder($config),
            'ByteFallback' => new ByteFallback($config),
            'Fuse' => new FuseDecoder($config),
            'Strip' => new StripDecoder($config),
            'Sequence' => new DecoderSequence($config),
            'CTC' => new CTCDecoder($config),
            'BPEDecoder' => new BPEDecoder($config),
            default => throw new \InvalidArgumentException("Unknown decoder type: {$config['type']}"),
        };
    }

    /**
     * Decodes a list of tokens.
     *
     * @param string[] $tokens
     * @return string
     */
    public function decode(array $tokens): string
    {
        return implode('', $this->decodeChain($tokens));
    }

    /**
     * Apply the decoder to a list of tokens.
     *
     * @param string[] $tokens The list of tokens.
     * @return string[] The decoded list of tokens.
     */
    abstract protected function decodeChain(array $tokens): array;

    /**
     * Decodes a list of tokens.
     *
     * @param string[] $tokens
     * @return string
     */
    public function __invoke(array $tokens): string
    {
        return $this->decode($tokens);
    }


}