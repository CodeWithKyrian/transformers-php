<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

use Codewithkyrian\Transformers\Tokenizers\Tokenizer;

/**
 * A subclass of TokenizerModel that uses WordPiece encoding to encode tokens.
 */
class WordPieceTokenizer extends Tokenizer
{
    protected int $maxInputCharsPerWord;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->tokenToIds = self::toMap($config['vocab']);
        $this->unkToken = $this->config['unk_token'] ?? '[UNK]';
        $this->unkTokenId = $this->tokenToIds[$this->unkToken];

        $this->maxInputCharsPerWord = $this->config['max_input_chars_per_word'] ?? 100;

        foreach ($this->tokenToIds as $token => $id) {
            $this->vocab[$id] = $token;
        }
    }

    /**
     * Encodes an array of tokens using WordPiece encoding.
     * @param string[] $tokens The tokens to encode.
     * @return string[] The encoded token IDs.
     */
    protected function encode(array $tokens): array
    {
        $outputTokens = [];

        foreach ($tokens as $token) {
            $chars = str_split($token);

            if (count($chars) > $this->maxInputCharsPerWord) {
                $outputTokens[] = $this->unkToken;
                continue;
            }

            $isUnknown = false;
            $start = 0;
            $subTokens = [];

            while ($start < count($chars)) {
                $end = count($chars);
                $currentSubstring = null;

                while ($start < $end) {
                    $substr = implode('', array_slice($chars, $start, $end - $start));

                    if ($start > 0) {
                        $substr = $this->config['continuing_subword_prefix'] . $substr;
                    }

                    if (array_key_exists($substr, $this->tokenToIds)) {
                        $currentSubstring = $substr;
                        break;
                    }

                    --$end;
                }

                if ($currentSubstring === null) {
                    $isUnknown = true;
                    break;
                }

                $subTokens[] = $currentSubstring;
                $start = $end;
            }

            if ($isUnknown) {
                $outputTokens[] = $this->unkToken;
            } else {
                $outputTokens = array_merge($outputTokens, $subTokens);
            }
        }

        return $outputTokens;
    }
}