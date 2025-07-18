<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

use Codewithkyrian\Transformers\Transformers;

class XLMTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;

    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);

        $logger = Transformers::getLogger();
        $logger->warning("`XLMTokenizer` is not yet supported by Hugging Face\'s `fast` tokenizers library. Therefore, you may experience slightly inaccurate results.");
    }
}
