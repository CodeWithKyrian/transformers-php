<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

class HerbertTokenizer extends PretrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}