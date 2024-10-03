<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

/**
 * Albert tokenizer
 */
class AlbertTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
