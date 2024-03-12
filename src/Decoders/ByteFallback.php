<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

class ByteFallback extends Decoder
{

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function decodeChain(array $tokens): array
    {
        $new_tokens = [];
        $previous_byte_tokens = [];

        foreach ($tokens as $token) {
            $bytes = null;
            if (strlen($token) === 6 && str_starts_with($token, '<0x') && str_ends_with($token, '>')) {
                $byte = hexdec(substr($token, 3, 2));
                if (!is_nan($byte)) {
                    $bytes = $byte;
                }
            }
            if ($bytes !== null) {
                $previous_byte_tokens[] = $bytes;
            } else {
                if (count($previous_byte_tokens) > 0) {
                    $string = $this->bytesToString($previous_byte_tokens);
                    $new_tokens[] = $string;
                    $previous_byte_tokens = [];
                }
                $new_tokens[] = $token;
            }
        }
        if (count($previous_byte_tokens) > 0) {
            $string = $this->bytesToString($previous_byte_tokens);
            $new_tokens[] = $string;
        }

        return $new_tokens;
    }

    /**
     * Convert an array of byte values back to a string.
     *
     * @param array $bytes An array of byte values.
     * @return string The resulting string after conversion.
     */
    protected function bytesToString(array $bytes): string
    {
        $chars = array_map(function ($byte) {
            return chr($byte);
        }, $bytes);
        return implode('', $chars);
    }
}