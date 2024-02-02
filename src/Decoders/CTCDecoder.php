<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

use Codewithkyrian\Transformers\Tokenizers\Tokenizer;

/**
 * The CTC (Connectionist Temporal Classification) decoder.
 * See https://github.com/huggingface/tokenizers/blob/bb38f390a61883fc2f29d659af696f428d1cda6b/tokenizers/src/decoders/ctc.rs
 */
class CTCDecoder extends Decoder
{
    /**
     * @var mixed|null
     */
    protected string $padToken;
    /**
     * @var mixed|null
     */
    protected string $wordDelimiterToken;
    /**
     * @var mixed|true
     */
    protected mixed $cleanup;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->padToken = $config['pad_token'] ?? null;
        $this->wordDelimiterToken = $config['word_delimiter_token'] ?? null;
        $this->cleanup = $config['cleanup'] ?? true;
    }

    /**
     * Converts a connectionist-temporal-classification (CTC) output tokens into a single string.
     * @param array $tokens Array of tokens to be decoded.
     * @return string The decoded string.
     */
    function convertTokensToString(array $tokens): string
    {
        if (empty($tokens)) {
            return '';
        }

        // Group same tokens into non-repeating tokens in CTC style decoding
        $groupedTokens = [$tokens[0]];
        for ($i = 1; $i < count($tokens); ++$i) {
            if ($tokens[$i] !== end($groupedTokens)) {
                $groupedTokens[] = $tokens[$i];
            }
        }

        // Filter $this.padToken which is used as CTC-blank token
        $filteredTokens = array_filter($groupedTokens, function ($token) {
            return $token !== $this->padToken;
        });

        $text = implode('', $filteredTokens);
        if ($this->cleanup) {
            // Cleanup and replace delimiter token
            $text = trim(str_replace($this->wordDelimiterToken, ' ', Tokenizer::cleanUpTokenization($text)));
        }

        return $text;
    }

    protected function decodeChain(array $tokens): array
    {
        return [$this->convertTokensToString($tokens)];
    }
}