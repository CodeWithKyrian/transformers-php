<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

enum AggregationStrategy
{
    case NONE;
    case SIMPLE;
    case FIRST;
    case AVERAGE;
    case MAX;
}
