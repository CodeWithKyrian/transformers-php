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


    public function __construct(protected array $config)
    {
    }

    protected function preTokenizeText(array|string $text, array $options): array
    {
        // TODO: Implement preTokenizeText() method.
    }
}