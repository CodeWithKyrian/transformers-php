<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

use Codewithkyrian\Transformers\Tokenizers\AddedToken;
use Codewithkyrian\Transformers\Tokenizers\Tokenizer;
use SplFixedArray;

class ByteLevelDecoder extends Decoder
{
    protected array $byteDecoder = [];

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->byteDecoder = Tokenizer::unicodeToBytes();
    }


    /**
     * Convert an array of tokens to a string by decoding each byte.
     *
     * @param array $tokens Array of tokens to be decoded.
     * @return string The decoded string.
     */
    public function convertTokensToString(array $tokens): string
    {
        $text = implode('', $tokens);

        $textArray = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $byteArray = array_map(fn($x) => $this->byteDecoder[$x], $textArray);

        $binaryString = pack('C*', ...$byteArray);

        return mb_convert_encoding($binaryString, 'ISO-8859-1');
    }

    protected function decodeChain(array $tokens): array
    {
        $subTexts = [];
        $currentSubText = [];

        foreach ($tokens as $token) {
            // No need to check skip_special_tokens since the tokens are already filtered

            $addedToken = array_filter($this->addedTokens, function (AddedToken $x) use ($token) {
                return $x->content === $token;
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