<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use Exception;

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


function timeUsage(bool $milliseconds = false, bool $sinceLastCall = true): string
{
    static $lastCallTime = 0;

    $currentTime = microtime(true);

    $timeDiff = $sinceLastCall ? ($lastCallTime !== 0 ? $currentTime - $lastCallTime
        : $currentTime - $_SERVER["REQUEST_TIME_FLOAT"])
        : $currentTime - $_SERVER["REQUEST_TIME_FLOAT"];

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

function camelCaseToSnakeCase(string $input): string
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
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

/**
 * Prepare images for further tasks.
 * @param mixed $images Images to prepare.
 * @return Image[] Returns processed images.
 */
function prepareImages(mixed $images): array
{
    if (!is_array($images)) {
        $images = [$images];
    }

    // Possibly convert any non-images to images
    $processedImages = [];

    foreach ($images as $image) {
        $processedImages[] = Image::read($image);
    }

    return $processedImages;
}

/**
 * Helper function to convert list [xmin, xmax, ymin, ymax] into object { "xmin": xmin, ... }
 * @param array $box The bounding box as a list.
 * @param bool $asInteger Whether to cast to integers.
 * @return array The bounding box as an object.
 * @private
 */
function getBoundingBox(array $box, bool $asInteger): array
{
    if ($asInteger) {
        $box = array_map(fn($x) => (int)$x, $box);
    }

    [$xmin, $ymin, $xmax, $ymax] = $box;

    return ['xmin' => $xmin, 'ymin' => $ymin, 'xmax' => $xmax, 'ymax' => $ymax];
}

