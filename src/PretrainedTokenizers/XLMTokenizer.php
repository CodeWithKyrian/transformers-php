<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

class XLMTokenizer extends PretrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;

    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);

        trigger_error("WARNING: `XLMTokenizer` is not yet supported by Hugging Face\'s `fast` tokenizers library. Therefore, you may experience slightly inaccurate results.");
    }
}