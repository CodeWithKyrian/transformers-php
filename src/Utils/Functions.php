<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

function memoryUsage(): string
{
    $mem = memory_get_usage(true);
    $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    return @round($mem / pow(1024, ($i = floor(log($mem, 1024)))), 2) . ' ' . $unit[$i];
}

function memoryPeak(): string
{
    $mem = memory_get_peak_usage(true);
    $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    return @round($mem / pow(1024, ($i = floor(log($mem, 1024)))), 2) . ' ' . $unit[$i];
}


function timeUsage(bool $milliseconds = false, bool $sinceLastCall = false): string
{
    static $lastCallTime;

    $currentTime = microtime(true);

    $timeDiff = $currentTime - ($sinceLastCall ? $lastCallTime : $_SERVER["REQUEST_TIME_FLOAT"]);

    $lastCallTime = $currentTime;

    $timeDiff = $milliseconds ? $timeDiff * 1000 : $timeDiff;

    return @round($timeDiff, 4) . ($milliseconds ? ' ms' : ' s');
}