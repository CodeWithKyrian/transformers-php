<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class ElectraTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
