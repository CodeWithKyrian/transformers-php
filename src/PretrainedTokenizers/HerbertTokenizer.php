<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

class HerbertTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
