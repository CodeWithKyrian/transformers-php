<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

class DecoderSequence extends Decoder
{
    /**
     * @var array Decoder[]
     */
    protected array $decoders;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->decoders = array_map(
            fn(array $decoderConfig) => Decoder::fromConfig($decoderConfig),
            $config['decoders']
        );
    }

    protected function decodeChain(array $tokens): array
    {
        return array_reduce(
            $this->decoders,
            fn(array $tokens, Decoder $decoder) => $decoder->decodeChain($tokens),
            $tokens
        );
    }
}