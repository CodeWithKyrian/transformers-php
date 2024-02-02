<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

class SqueezeBertTokenizer extends PretrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}