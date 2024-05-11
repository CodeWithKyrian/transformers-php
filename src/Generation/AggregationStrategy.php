<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation;

enum AggregationStrategy: string
{
    case NONE = 'none';
    case FIRST = 'first';
    case AVERAGE = 'average';
    case MAX = 'max';
}
