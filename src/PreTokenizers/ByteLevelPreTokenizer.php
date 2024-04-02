<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

use Codewithkyrian\Transformers\Tokenizers\Tokenizer;

/**
 * A pre-tokenizer that splits text into Byte-Pair-Encoding (BPE) subwords.
 */
class ByteLevelPreTokenizer extends PreTokenizer
{

    /**
     * Whether to add a leading space to the first word.
     * This allows to treat the leading word just as any other word.
     */
    protected bool $addPrefixSpace;

    /**
     * Whether the post-processing step should trim offsets to avoid including whitespaces.
     */
    protected bool $trimOffsets;

    /**
     * Whether to use the standard GPT2 regex for whitespace splitting.
     *  Set it to false if you want to use your own splitting. Defaults to true.
     */
    protected bool $useRegex;

    protected string $pattern;

    protected array $byteEncoder;


    public function __construct(protected array $config)
    {
        $this->addPrefixSpace = $config['add_prefix_space'] ?? true;
        $this->trimOffsets = $config['trim_offsets'] ?? true;
        $this->useRegex = $config['use_regex'] ?? true;

        if ($this->useRegex) {
//            $this->pattern = "/'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+/gu";
            $this->pattern = "/'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+/u";


        }

        $this->byteEncoder = Tokenizer::bytesToUnicode();
    }

    protected function preTokenizeText(array|string $text, array $options): array
    {
        // Add a leading space if the option is enabled
        if ($this->addPrefixSpace && !str_starts_with($text, ' ')) {
            $text = ' ' . $text;
        }

        // Split on whitespace and punctuation
        if ($this->useRegex) {
            preg_match_all($this->pattern, $text, $matches);
            $tokens = $matches[0];
        } else {
            $tokens = [$text];
        }


        // Maps all our bytes to unicode strings, avoiding control tokens of the BPE (spaces in our case)
        return array_map(function ($token) {
            $utf8Bytes = mb_convert_encoding($token, 'UTF-8');
            $bytes = array_map(function ($byte) {
                return $this->byteEncoder[$byte];
            }, unpack('C*', $utf8Bytes));

            return implode('', $bytes);
        }, $tokens);

    }

}