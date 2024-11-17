<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

function memoryUsage(): string
{
    $mem = memory_get_usage(true);
    $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    return @round($mem / pow(1024, ($i = floor(log($mem, 1024)))), 2).' '.$unit[$i];
}

function memoryPeak(): string
{
    $mem = memory_get_peak_usage(true);
    $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    return @round($mem / pow(1024, ($i = floor(log($mem, 1024)))), 2).' '.$unit[$i];
}


function timeUsage(bool $milliseconds = false, bool $sinceLastCall = true, bool $returnString = true): string|float
{
    static $lastCallTime = 0;

    $currentTime = microtime(true);

    $timeDiff = $sinceLastCall ? ($lastCallTime !== 0 ? $currentTime - $lastCallTime
        : $currentTime - $_SERVER["REQUEST_TIME_FLOAT"])
        : $currentTime - $_SERVER["REQUEST_TIME_FLOAT"];

    $lastCallTime = $currentTime;

    $timeDiff = $milliseconds ? $timeDiff * 1000 : $timeDiff;

//    return @round($timeDiff, 4) . ($milliseconds ? ' ms' : ' s');
    return $returnString ? @round($timeDiff, 4).($milliseconds ? ' ms' : ' s') : @round($timeDiff, 4);
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

function array_pop_key(array &$array, string|int $key, mixed $default = null)
{
    if (isset($array[$key])) {
        $value = $array[$key];
        unset($array[$key]);
        return $value;
    }
    return $default;
}

function array_keys_to_snake_case(array $array): array
{
    $snakeCasedArray = [];

    foreach ($array as $key => $value) {
        $snakeCasedArray[camelCaseToSnakeCase($key)] = $value;
    }

    return $snakeCasedArray;
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
            $paths[$key] = rtrim($path, DIRECTORY_SEPARATOR);
        } elseif ($key === count($paths) - 1) {
            $paths[$key] = ltrim($path, DIRECTORY_SEPARATOR);
        } else {
            $paths[$key] = trim($path, DIRECTORY_SEPARATOR);
        }
    }

    return preg_replace('#(?<!:)//+#', '/', implode(DIRECTORY_SEPARATOR, $paths));
}

function ensureDirectory($filePath): void
{
    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0755, true);
    }
}

/**
 * Prepare images for further tasks.
 *
 * @param mixed $images Images to prepare.
 *
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
 *
 * @param array $box The bounding box as a list.
 * @param bool $asInteger Whether to cast to integers.
 *
 * @return array The bounding box as an object.
 * @private
 */
function getBoundingBox(array $box, bool $asInteger): array
{
    if ($asInteger) {
        $box = array_map(fn ($x) => (int)$x, $box);
    }

    [$xmin, $ymin, $xmax, $ymax] = $box;

    return ['xmin' => $xmin, 'ymin' => $ymin, 'xmax' => $xmax, 'ymax' => $ymax];
}


/**
 * Returns base path value of the project
 *
 * @param string $dir Directory to append to base path
 *
 * @return string
 */
function basePath(string $dir = ""): string
{
    return joinPaths(dirname(__DIR__, 2), $dir);
}

/**
 * Helper method to construct a pattern from a config object.
 *
 * @param array $pattern The pattern object.
 * @param bool $invert Whether to invert the pattern.
 *
 * @return string|null The compiled pattern or null if invalid.
 */
function createPattern(array $pattern, bool $invert = true): ?string
{
    if (isset($pattern['Regex'])) {
        // Remove unnecessary escape sequences
        return str_replace(['\\#', '\\&', '\\~'], ['#', '&', '~'], $pattern['Regex']);
    } elseif (isset($pattern['String'])) {
        $escaped = preg_quote($pattern['String'], '/');

        // NOTE: if invert is true, we wrap the pattern in a group so that it is kept when performing split
        return $invert ? $escaped : "($escaped)";
    } else {
        echo 'Unknown pattern type: '.print_r($pattern, true);
        return null;
    }
}
