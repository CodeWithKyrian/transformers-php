<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

class Math
{
    /**
     * Compute the softmax of an array of numbers.
     * @template T of int|float
     * @param array<int|float> $arr The array of numbers to compute the softmax of.
     * @return array<int|float> The softmax array.
     */
    public static function softmax(array $arr): array
    {
        // Compute the maximum value in the array
        $maxVal = max($arr);

        // Compute the exponentials of the array values
        $exps = array_map(fn($x) => exp($x - $maxVal), $arr);

        // Compute the sum of the exponentials
        $sumExps = array_sum($exps);

        // Compute the softmax values
        return array_map(fn($x) => $x / $sumExps, $exps);
    }

    /**
     * Calculates the logarithm of the softmax function for the input array.
     * @template T of int|float
     * @param array<int|float> $arr The input array to calculate the log_softmax function for.
     * @return array<int|float> The resulting log_softmax array.
     */
    public static function logSoftmax(array $arr): array
    {
        // Compute the softmax values
        $softmaxArr = self::softmax($arr);

        // Apply log formula to each element
        return array_map(fn($x) => log($x), $softmaxArr);
    }

    /**
     * Get the top k items from an iterable, sorted by descending order
     * @param array|\Traversable $items The items to be sorted
     * @param int $topK The number of top items to return (default: 0 = return all)
     * @return array The top k items, sorted by descending order
     */

    public static function getTopItems(array $items, int $topK = -1): array
    {
        $indexedItems = [];
        foreach ($items as $index => $value) {
            $indexedItems[] = [$index, $value];
        }

        usort($indexedItems, function ($a, $b) {
            return $b[1] <=> $a[1];
        });

        // Get top k items if top_k > 0
        if ($topK !== -1 && $topK > 0) {
            $indexedItems = array_slice($indexedItems, 0, $topK);
        }

        return $indexedItems;
    }


    /**
     * Compute the Cartesian product of given arrays
     * @param array ...$a Arrays to compute the product
     * @return array Returns the computed Cartesian product as an array
     */
    public static function product(...$a): array
    {
        // Cartesian product of items
        // Adapted from https://stackoverflow.com/a/43053803

        return array_reduce($a, function ($carry, $array) {
            return array_merge(
                ...array_map(function ($d) use ($array) {
                    return array_map(function ($e) use ($d) {
                        return [...$d, $e];
                    }, $array);
                }, $carry)
            );
        }, [[]]);
    }
}