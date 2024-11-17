<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

use Codewithkyrian\Transformers\Tokenizers\AddedToken;
use Codewithkyrian\Transformers\Tokenizers\TokenizerModel;
use SplFixedArray;

class ByteLevelDecoder extends Decoder
{
    protected const UNICODE_TO_BYTES = [
        'Ā' => 0,
        'ā' => 1,
        'Ă' => 2,
        'ă' => 3,
        'Ą' => 4,
        'ą' => 5,
        'Ć' => 6,
        'ć' => 7,
        'Ĉ' => 8,
        'ĉ' => 9,
        'Ċ' => 10,
        'ċ' => 11,
        'Č' => 12,
        'č' => 13,
        'Ď' => 14,
        'ď' => 15,
        'Đ' => 16,
        'đ' => 17,
        'Ē' => 18,
        'ē' => 19,
        'Ĕ' => 20,
        'ĕ' => 21,
        'Ė' => 22,
        'ė' => 23,
        'Ę' => 24,
        'ę' => 25,
        'Ě' => 26,
        'ě' => 27,
        'Ĝ' => 28,
        'ĝ' => 29,
        'Ğ' => 30,
        'ğ' => 31,
        'Ġ' => 32,
        '!' => 33,
        '"' => 34,
        '#' => 35,
        '$' => 36,
        '%' => 37,
        '&' => 38,
        '\'' => 39,
        '(' => 40,
        ')' => 41,
        '*' => 42,
        '+' => 43,
        ',' => 44,
        '-' => 45,
        '.' => 46,
        '/' => 47,
        '0' => 48,
        '1' => 49,
        '2' => 50,
        '3' => 51,
        '4' => 52,
        '5' => 53,
        '6' => 54,
        '7' => 55,
        '8' => 56,
        '9' => 57,
        ':' => 58,
        ';' => 59,
        '<' => 60,
        '=' => 61,
        '>' => 62,
        '?' => 63,
        '@' => 64,
        'A' => 65,
        'B' => 66,
        'C' => 67,
        'D' => 68,
        'E' => 69,
        'F' => 70,
        'G' => 71,
        'H' => 72,
        'I' => 73,
        'J' => 74,
        'K' => 75,
        'L' => 76,
        'M' => 77,
        'N' => 78,
        'O' => 79,
        'P' => 80,
        'Q' => 81,
        'R' => 82,
        'S' => 83,
        'T' => 84,
        'U' => 85,
        'V' => 86,
        'W' => 87,
        'X' => 88,
        'Y' => 89,
        'Z' => 90,
        '[' => 91,
        '\\' => 92,
        ']' => 93,
        '^' => 94,
        '_' => 95,
        '`' => 96,
        'a' => 97,
        'b' => 98,
        'c' => 99,
        'd' => 100,
        'e' => 101,
        'f' => 102,
        'g' => 103,
        'h' => 104,
        'i' => 105,
        'j' => 106,
        'k' => 107,
        'l' => 108,
        'm' => 109,
        'n' => 110,
        'o' => 111,
        'p' => 112,
        'q' => 113,
        'r' => 114,
        's' => 115,
        't' => 116,
        'u' => 117,
        'v' => 118,
        'w' => 119,
        'x' => 120,
        'y' => 121,
        'z' => 122,
        '{' => 123,
        '|' => 124,
        '}' => 125,
        '~' => 126,
        'ġ' => 127,
        'Ģ' => 128,
        'ģ' => 129,
        'Ĥ' => 130,
        'ĥ' => 131,
        'Ħ' => 132,
        'ħ' => 133,
        'Ĩ' => 134,
        'ĩ' => 135,
        'Ī' => 136,
        'ī' => 137,
        'Ĭ' => 138,
        'ĭ' => 139,
        'Į' => 140,
        'į' => 141,
        'İ' => 142,
        'ı' => 143,
        'Ĳ' => 144,
        'ĳ' => 145,
        'Ĵ' => 146,
        'ĵ' => 147,
        'Ķ' => 148,
        'ķ' => 149,
        'ĸ' => 150,
        'Ĺ' => 151,
        'ĺ' => 152,
        'Ļ' => 153,
        'ļ' => 154,
        'Ľ' => 155,
        'ľ' => 156,
        'Ŀ' => 157,
        'ŀ' => 158,
        'Ł' => 159,
        'ł' => 160,
        '¡' => 161,
        '¢' => 162,
        '£' => 163,
        '¤' => 164,
        '¥' => 165,
        '¦' => 166,
        '§' => 167,
        '¨' => 168,
        '©' => 169,
        'ª' => 170,
        '«' => 171,
        '¬' => 172,
        'Ń' => 173,
        '®' => 174,
        '¯' => 175,
        '°' => 176,
        '±' => 177,
        '²' => 178,
        '³' => 179,
        '´' => 180,
        'µ' => 181,
        '¶' => 182,
        '·' => 183,
        '¸' => 184,
        '¹' => 185,
        'º' => 186,
        '»' => 187,
        '¼' => 188,
        '½' => 189,
        '¾' => 190,
        '¿' => 191,
        'À' => 192,
        'Á' => 193,
        'Â' => 194,
        'Ã' => 195,
        'Ä' => 196,
        'Å' => 197,
        'Æ' => 198,
        'Ç' => 199,
        'È' => 200,
        'É' => 201,
        'Ê' => 202,
        'Ë' => 203,
        'Ì' => 204,
        'Í' => 205,
        'Î' => 206,
        'Ï' => 207,
        'Ð' => 208,
        'Ñ' => 209,
        'Ò' => 210,
        'Ó' => 211,
        'Ô' => 212,
        'Õ' => 213,
        'Ö' => 214,
        '×' => 215,
        'Ø' => 216,
        'Ù' => 217,
        'Ú' => 218,
        'Û' => 219,
        'Ü' => 220,
        'Ý' => 221,
        'Þ' => 222,
        'ß' => 223,
        'à' => 224,
        'á' => 225,
        'â' => 226,
        'ã' => 227,
        'ä' => 228,
        'å' => 229,
        'æ' => 230,
        'ç' => 231,
        'è' => 232,
        'é' => 233,
        'ê' => 234,
        'ë' => 235,
        'ì' => 236,
        'í' => 237,
        'î' => 238,
        'ï' => 239,
        'ð' => 240,
        'ñ' => 241,
        'ò' => 242,
        'ó' => 243,
        'ô' => 244,
        'õ' => 245,
        'ö' => 246,
        '÷' => 247,
        'ø' => 248,
        'ù' => 249,
        'ú' => 250,
        'û' => 251,
        'ü' => 252,
        'ý' => 253,
        'þ' => 254,
        'ÿ' => 255,
    ];


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

        $byteArray = array_map(fn($x) => self::UNICODE_TO_BYTES[$x], $textArray);

        $binaryString = pack('C*', ...$byteArray);

        return mb_convert_encoding($binaryString, 'UTF-8');
    }

    protected function decodeChain(array $tokens): array
    {
        $subTexts = [];
        $currentSubText = [];

        foreach ($tokens as $token) {
            // No need to check skip_special_tokens since the tokens are already filtered

            $addedToken = array_filter($this->addedTokens, fn (AddedToken $x) => $x->content === $token);

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
