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
        $newTokens = [];
        $previousByteTokens = [];

        foreach ($tokens as $token) {
            $bytes = null;
            if (strlen($token) === 6 && str_starts_with($token, '<0x') && str_ends_with($token, '>')) {
                $byte = hexdec(substr($token, 3, 2));
                if (!is_nan($byte)) {
                    $bytes = $byte;
                }
            }
            if ($bytes !== null) {
                $previousByteTokens[] = $bytes;
            } else {
                if (count($previousByteTokens) > 0) {
                    $string = $this->bytesToString($previousByteTokens);
                    $newTokens[] = $string;
                    $previousByteTokens = [];
                }
                $newTokens[] = $token;
            }
        }
        if (count($previousByteTokens) > 0) {
            $string = $this->bytesToString($previousByteTokens);
            $newTokens[] = $string;
        }

        return $newTokens;
    }

    /**
     * Convert an array of byte values back to a string.
     *
     * @param array $bytes An array of byte values.
     * @return string The resulting string after conversion.
     */
    protected function bytesToString(array $bytes): string
    {
        $binaryString = pack('C*', ...$bytes);
        return mb_convert_encoding($binaryString, 'ISO-8859-1');
    }
}