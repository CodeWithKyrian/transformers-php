<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class ConvBertTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
