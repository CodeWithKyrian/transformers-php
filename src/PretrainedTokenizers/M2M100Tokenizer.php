<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

use Codewithkyrian\Transformers\Tokenizers\BatchEncoding;

class M2M100Tokenizer extends PretrainedTokenizer
{
    protected string $languageRegex = '/^__[a-z]{2,3}__$/';

    protected array $languageCodes = [];
    protected \Closure $langToToken;

    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);


        $this->languageCodes = array_filter($this->specialTokens, function ($x) {
            return preg_match($this->languageRegex, $x);
        });

        $this->langToToken = fn($x) => "__{$x}__";
    }


    /**
     * Helper function to build translation inputs for an `MBartTokenizer`.
     *
     * @param string|string[] $rawInputs The text to tokenize.
     * @param $tokenizerOptions
     * @param $generateKwargs
     * @throws \Exception
     */
    public function buildTranslationInputs(string|array $rawInputs, $tokenizerOptions, $generateKwargs): BatchEncoding
    {

        $srcLangToken = $generateKwargs['src_lang'] ?? null;
        $tgtLangToken = $generateKwargs['tgt_lang'];

        // Check that the target language is valid:
        if (!in_array($tgtLangToken, $this->languageCodes)) {
            throw new \Exception("Target language code \"$tgtLangToken\" is not valid. Must be one of: {" . implode(', ', $this->languageCodes) . "}");
        }

        // Allow `src_lang` to be optional. If not set, we'll use the tokenizer's default.
        if ($srcLangToken !== null) {
            // Check that the source language is valid:
            if (!in_array($srcLangToken, $this->languageCodes)) {
                throw new \Exception("Source language code \"$srcLangToken\" is not valid. Must be one of: {" . implode(', ', $this->languageCodes) . "}");
            }

            // In the same way as the Python library, we override the post-processor
            // to force the source language to be first:
            foreach ($this->postProcessor->config['single'] as &$item) {
                if (isset($item['SpecialToken']) && preg_match($this->languageRegex, $item['SpecialToken']['id'])) {
                    $item['SpecialToken']['id'] = call_user_func($this->langToToken, $srcLangToken);
                    break;
                }
            }
            // TODO: Do the same for pair?
        }

        // Override the `forced_bos_token_id` to force the correct language
        $generateKwargs['forced_bos_token_id'] = $this->tokenizer->convertTokensToIds(
            [call_user_func($this->langToToken, $tgtLangToken)]
        )[0];

        return $this->__invoke($rawInputs, $tokenizerOptions);
    }
}