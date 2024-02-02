<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

class BPEDecoder extends Decoder
{
    protected string $suffix;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->suffix = $config['suffix'];
    }

    protected function decodeChain(array $tokens): array
    {
        return array_map(function (string $token, int $i) use ($tokens) {
            return str_replace($this->suffix, ($i === count($tokens) - 1) ? '' : ' ', $token);
        }, $tokens, array_keys($tokens));
    }
}