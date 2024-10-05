<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class MobileBertTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
