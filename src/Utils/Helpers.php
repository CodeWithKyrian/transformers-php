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

function array_some(array $array, callable $callback): bool
{
    foreach ($array as $key => $value) {
        if ($callback($value, $key)) {
            return true;
        }
    }

    return false;
}

function array_every(array $array, callable $callback): bool
{
    foreach ($array as $key => $value) {
        if (!$callback($value, $key)) {
            return false;
        }
    }

    return true;
}

function joinPaths(string ...$args): string
{
    $paths = [];

    foreach ($args as $key => $path) {
        if ($path === '') {
            continue;
        } elseif ($key === 0) {
            $paths[$key] = rtrim($path, '/');
        } elseif ($key === count($paths) - 1) {
            $paths[$key] = ltrim($path, '/');
        } else {
            $paths[$key] = trim($path, '/');
        }
    }

    return preg_replace('#/+#', '/', implode(DIRECTORY_SEPARATOR, $paths));
}

function ensureDirectory($filePath): void
{
    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0755, true);
    }
}