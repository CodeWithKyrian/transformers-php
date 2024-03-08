<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

/**
 * A callable class representing a pre-tokenizer used in tokenization.
 */
abstract class PreTokenizer
{
    public const PUNCTUATION_REGEX = '\p{P}\u0021-\u002F\u003A-\u0040\u005B-\u0060\u007B-\u007E';

    public static function fromConfig(?array $config): ?self
    {
        if ($config === null) {
            return null;
        }

        return match ($config['type']) {
            'BertPreTokenizer' => new BertPreTokenizer($config),
            'Sequence' => new PreTokenizerSequence($config),
            'WhitespaceSplit' => new WhitespaceSplit($config),
            'Metaspace' => new MetaspacePreTokenizer($config),
            'ByteLevel' => new ByteLevelPreTokenizer($config),
            'Split' => new SplitPreTokenizer($config),
            'Punctuation' => new PunctuationPreTokenizer($config),
            'Digits' => new DigitsPreTokenizer($config),
            'Replace' => new ReplacePreTokenizer($config),
            default => throw new \InvalidArgumentException("Unknown pre-tokenizer type {$config['type']}"),
        };
    }

    /**
     * Method that should be implemented by subclasses to define the specific pre-tokenization logic.
     *
     * @param string|string[] $text The text to pre-tokenize.
     * @param array $options Additional options for the pre-tokenization logic.
     * @return string[] The pre-tokenized text.
     */
    protected abstract function preTokenizeText(string|array $text, array $options): array;


    /** Tokenizes the given text into pre-tokens.
     * @param string|string[] $text The text to pre-tokenize.
     * @param array $options Additional options for the pre-tokenization logic.
     * @return string[]
     */
    public function preTokenize(string|array $text, array $options): array
    {
        // Check if $text is an array
        if (is_array($text)) {
            $result = array_map(function ($x) use ($options) {
                return $this->preTokenizeText($x, $options);
            }, $text);

            return array_merge(...$result);
        } else {
            // If $text is not an array, apply pre_tokenize_text directly
            return $this->preTokenizeText($text, $options);
        }
    }

    /** Tokenizes the given text into pre-tokens.
     * @param string|string[] $text The text to pre-tokenize.
     * @param array $options Additional options for the pre-tokenization logic.
     * @return string[]
     */
    public function __invoke(string|array $text, array $options): array
    {
        return $this->preTokenize($text, $options);
    }
}