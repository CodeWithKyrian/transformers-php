<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

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
        $this->addPrefixSpace = $config['add_prefix_state'] ?? true;
        $this->trimOffsets = $config['trim_offsets'] ?? true;
        $this->useRegex = $config['use_regex'] ?? true;

        if ($this->useRegex) {
            $this->pattern = "/'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+/gu";
        }

        $this->byteEncoder = $this->bytesToUnicode();
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

    /**
     * Returns list of utf-8 byte and a mapping to unicode strings.
     * Specifically avoids mapping to whitespace/control characters the BPE code barfs on.
     * @returns array Object with utf-8 byte keys and unicode string values.
     */
    protected function bytesToUnicode(): array
    {
        // Returns list of utf-8 byte and a mapping to unicode strings.
        // Specifically avoids mapping to whitespace/control characters the bpe code barfs on.

        $bs = array_merge(
            range(ord('!'), ord('~')),
            range(ord('¡'), ord('¬')),
            range(ord('®'), ord('ÿ'))
        );

        $cs = $bs;
        $n = 0;
        for ($b = 0; $b < 256; ++$b) {
            if (!in_array($b, $bs)) {
                $bs[] = $b;
                $cs[] = 256 + $n;
                $n += 1;
            }
        }
        $ccs = array_map('chr', $cs);
        return array_combine($bs, $ccs);
    }

}