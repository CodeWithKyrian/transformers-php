<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class RoFormerTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
