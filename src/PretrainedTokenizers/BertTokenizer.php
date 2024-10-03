<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

/**
 * BertTokenizer is a class used to tokenize text for BERT models.
 */
class BertTokenizer extends PreTrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}
