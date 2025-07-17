<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Normalizers;

/**
 * A class representing a normalizer used in BERT tokenization.
 */
class BertNormalizer extends Normalizer
{
    /**
     * Performs invalid character removal and whitespace cleanup on text.
     * @param string $text The text to clean.
     * @return string The cleaned text.
     * @private
     */
    function cleanText(string $text): string
    {
        $output = [];
        for ($i = 0; $i < mb_strlen($text); ++$i) {
            $char = mb_substr($text, $i, 1);
            $cp = mb_ord($char);
            if ($cp === 0 || $cp === 0xFFFD || $this->isControl($char)) {
                continue;
            }
            if (preg_match('/^\s$/', $char)) { // is whitespace
                $output[] = " ";
            } else {
                $output[] = $char;
            }
        }
        return implode("", $output);
    }

    public function normalize(string $text): string
    {
        if ($this->config['clean_text'] ?? false) {
            $text = $this->cleanText($text);
        }

        if ($this->config['handle_chinese_chars'] ?? false) {
            $text = $this->tokenizeChineseChars($text);
        }

        if ($this->config['lowercase'] ?? false) {
            $text = mb_strtolower($text);
        }

        if ($this->config['strip_accents'] ?? false) {
            $text = $this->stripAccents($text);
        }

        return $text;
    }

    /**
     * Strips accents from the given text.
     * @param string $text The text to strip accents from.
     * @return string The text with accents removed.
     */
    protected function stripAccents(string $text): string
    {
        return normalizer_normalize($text, \Normalizer::NFD);
    }

    /**
     * Checks whether `$char` is a control character.
     * @param string $char The character to check.
     * @return bool Whether `$char` is a control character.
     * @private
     */
    protected function isControl(string $char): bool
    {
        return match ($char) {
            "\t", "\n", "\r" => false, // These are technically control characters but we count them as whitespace characters.
            // Check if unicode category starts with C:
            // Cc - Control
            // Cf - Format
            // Co - Private Use
            // Cs - Surrogate
            default => preg_match('/^\p{Cc}|\p{Cf}|\p{Co}|\p{Cs}$/u', $char) === 1,
        };
    }

    /**
     * Checks whether the given Unicode codepoint represents a CJK (Chinese, Japanese, or Korean) character.
     *
     * A "chinese character" is defined as anything in the CJK Unicode block.
     *
     * @param int $cp The Unicode codepoint to check.
     *
     * @return bool True if the codepoint represents a CJK character, false otherwise.
     */
    protected function isChineseChar(int $cp): bool
    {
        return (
            ($cp >= 0x4E00 && $cp <= 0x9FFF)
            || ($cp >= 0x3400 && $cp <= 0x4DBF)
            || ($cp >= 0x20000 && $cp <= 0x2A6DF)
            || ($cp >= 0x2A700 && $cp <= 0x2B73F)
            || ($cp >= 0x2B740 && $cp <= 0x2B81F)
            || ($cp >= 0x2B820 && $cp <= 0x2CEAF)
            || ($cp >= 0xF900 && $cp <= 0xFAFF)
            || ($cp >= 0x2F800 && $cp <= 0x2FA1F)
        );
    }

    /**
     * Adds whitespace around any CJK (Chinese, Japanese, or Korean) character in the input text.
     *
     * @param string $text The input text to tokenize.
     *
     * @return string The tokenized text with whitespace added around CJK characters.
     */
    public function tokenizeChineseChars(string $text): string
    {
        $output = [];
        for ($i = 0; $i < mb_strlen($text); ++$i) {
            $char = mb_substr($text, $i, 1);
            $cp = mb_ord($char);
            if ($this->isChineseChar($cp)) {
                $output[] = " ";
                $output[] = $char;
                $output[] = " ";
            } else {
                $output[] = $char;
            }
        }
        return implode("", $output);
    }
}
