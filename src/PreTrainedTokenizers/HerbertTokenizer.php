<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class HerbertTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
