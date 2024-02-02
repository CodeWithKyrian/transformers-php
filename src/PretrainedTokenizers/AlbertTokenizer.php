<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

/**
 * Albert tokenizer
 */
class AlbertTokenizer extends PretrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}