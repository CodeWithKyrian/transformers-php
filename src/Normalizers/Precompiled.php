<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Normalizers;

class Precompiled extends Normalizer
{

    /**
     * Precompiled chars mapping.
     */
    protected mixed $charsMap;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->charsMap = $config['precompiled_charsmap'];
    }

    /**
     * Normalizes the given text by applying the precompiled charsmap.
     * @param string $text The text to normalize.
     * @return string The normalized text.
     */
    public function normalize(string $text): string
    {
        // As stated in the sentencepiece normalization docs (https://github.com/google/sentencepiece/blob/master/doc/normalization.md#use-pre-defined-normalization-rule),
        // there are 5 pre-defined normalization rules:
        //  1. nmt_nfkc: NFKC normalization with some additional normalization around spaces. (default)
        //  2. nfkc: original NFKC normalization.
        //  3. nmt_nfkc_cf: nmt_nfkc + Unicode case folding (mostly lower casing)
        //  4. nfkc_cf: nfkc + Unicode case folding.
        //  5. identity: no normalization
        //
        // For now, we only implement the default (nmt_nfkc).
        // See https://raw.githubusercontent.com/google/sentencepiece/master/data/nmt_nfkc.tsv for the full list of rules.
        // TODO: detect when a different `this.charsmap` is used.


        // Remove control characters
        $text = preg_replace('/[\x01-\x08\x0B\x0E-\x1F\x7F\x8F\x9F]/u', '', $text);

        // Replace certain characters with a space
        $text = preg_replace('/[\x09\x0A\x0C\x0D\x1680\x200B\x200C\x200E\x200F\x2028\x2029\x2581\xFEFF\xFFFD]/u', ' ', $text);

        if (mb_strpos($text, '～') !== false) {
            // To match the sentencepiece implementation 100%, we must handle a very strange edge-case.
            // For some reason, the "Fullwidth Tilde" character (～) should not be converted to the standard Tilde character (~).
            // However, NFKC normalization does do this conversion. As a result, we split the string on the Fullwidth Tilde character,
            // perform NFKC normalization on each substring, and then join them back together with the Fullwidth Tilde character.
            $parts = explode('～', $text);
            $text = implode('～', array_map(function ($part) {
                return mb_convert_encoding(normalizer_normalize($part, \Normalizer::FORM_KC), 'UTF-8', 'UTF-8');
            }, $parts));
        } else {
            $text = normalizer_normalize($text, \Normalizer::FORM_KC);
        }

        return $text;
    }
}