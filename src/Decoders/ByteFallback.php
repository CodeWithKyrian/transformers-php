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
        $previousByteTokens = [];
        $newTokens = [];

        foreach ($tokens as $token) {
            $bytes = null;

            // Check if the token is of the form <0xXX>
            if (strlen($token) === 6 && str_starts_with($token, '<0x') && str_ends_with($token, '>')) {
                // Extract the hexadecimal value from the token
                $byte = hexdec(substr($token, 3, 2));
                if (!is_nan($byte)) {
                    $bytes = $byte;
                }
            }

            if ($bytes !== null) {
                // Add byte to previousByteTokens
                $previousByteTokens[] = $bytes;
            } else {
                // If we have accumulated byte tokens, decode them to a string
                if (!empty($previousByteTokens)) {
                    $string = pack('C*', ...$previousByteTokens);  // Convert bytes back to string
                    $newTokens[] = $string;  // Add decoded string to newTokens
                    $previousByteTokens = [];  // Reset byte accumulator
                }
                // Add the non-byte token to newTokens
                $newTokens[] = $token;
            }
        }


        // After the loop, if there are still byte tokens, decode them
        if (!empty($previousByteTokens)) {
            $string = pack('C*', ...$previousByteTokens);  // Convert remaining bytes to string
            $newTokens[] = $string;
            $previousByteTokens = [];  // Reset byte accumulator
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
