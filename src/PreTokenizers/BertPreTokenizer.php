<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

/**
 * A PreTokenizer that splits text into wordpieces using a basic tokenization scheme
 *  similar to that used in the original implementation of BERT.
 *
 * https://www.analyticsvidhya.com/blog/2021/09/an-explanatory-guide-to-bert-tokenizer/
 */
class BertPreTokenizer extends PreTokenizer
{
    protected string $pattern;

    public function __construct(array $config)
    {
        // Construct a pattern which matches the rust implementation:
        // https://github.com/huggingface/tokenizers/blob/b4fcc9ce6e4ad5806e82826f816acfdfdc4fcc67/tokenizers/src/pre_tokenizers/bert.rs#L11
        // Equivalent to removing whitespace and splitting on punctuation (both \p{P} and other ascii characters)
        $punctuationRegex = '\p{P}\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7E';
        $this->pattern = "/\s+|([$punctuationRegex])+/u";
    }

    protected function preTokenizeText(array|string $text, array $options): array
    {
        return preg_split($this->pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?? [];
    }
}