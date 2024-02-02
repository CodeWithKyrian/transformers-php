<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

use SplFixedArray;

class ByteLevelDecoder extends Decoder
{


    /**
     * Convert an array of tokens to a string by decoding each byte.
     *
     * @param array $tokens Array of tokens to be decoded.
     * @return string The decoded string.
     */
    public function convertTokensToString(array $tokens): string
    {
        $text = implode('', $tokens);
        $byteArray = new SplFixedArray(mb_strlen($text, '8bit'));

        for ($i = 0; $i < mb_strlen($text, '8bit'); ++$i) {
            $byteArray[$i] = ord($text[$i]);
        }

        return utf8_decode(implode('', iterator_to_array($byteArray, false)));
    }

    protected function decodeChain(array $tokens): array
    {
        $subTexts = [];
        $currentSubText = [];

        foreach ($tokens as $token) {
            // No need to check skip_special_tokens since the tokens are already filtered

            $addedToken = array_filter($this->addedTokens, function ($x) use ($token) {
                return $x['content'] === $token;
            });

            if (!empty($addedToken)) {
                if (!empty($currentSubText)) {
                    $subTexts[] = $this->convertTokensToString($currentSubText);
                    $currentSubText = [];
                }

                $subTexts[] = $token;
            } else {
                $currentSubText[] = $token;
            }
        }

        if (!empty($currentSubText)) {
            $subTexts[] = $this->convertTokensToString($currentSubText);
        }

        // TODO: add spaces_between_special_tokens and clean_up_tokenization_spaces options

        return $subTexts;
    }
}