<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

use Codewithkyrian\Transformers\Decoders\VitsDecoder;

class VitsTokenizer extends PretrainedTokenizer
{
    public function __construct(array $tokenizerJSON, ?array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);

        // Custom decoder function
        $this->decoder = new VitsDecoder([]);
    }
}
