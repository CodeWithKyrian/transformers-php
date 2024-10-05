<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class BloomTokenizer extends GPT2Tokenizer
{
    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {

        // Override the default (invalid) regex of the pretokenizer.
        // For more information, see https://github.com/xenova/transformers.js/issues/94
        $splitChars = '.,!?\u2026\u3002\uff0c\u3001\u0964\u06d4\u060c';

        $patternObject = $tokenizerJSON['pre_tokenizer']['pretokenizers'][0]['pattern'] ?? null;
        if ($patternObject && $patternObject['Regex'] === ' ?[^(\\s|[${splitChars}])]+') {
            $patternObject['Regex'] = " ?[^\\s{$splitChars}]+";
        }


        parent::__construct($tokenizerJSON, $tokenizerConfig);
    }
}
