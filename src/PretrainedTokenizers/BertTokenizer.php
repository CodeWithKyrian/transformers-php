<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

/**
 * BertTokenizer is a class used to tokenize text for BERT models.
 */
class BertTokenizer extends PretrainedTokenizer
{
    protected bool $returnTokenTypeIds = true;
}