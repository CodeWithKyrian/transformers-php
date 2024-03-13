<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Utils\Math;

it('calculates softmax correctly for positive values', function () {
    $inputArray = [2.0, 1.0, 0.1];
    $softmaxResult = Math::softmax($inputArray);

    expect($softmaxResult)->toBeArray()
        ->and($softmaxResult[0])->toBeApproximately(0.659, 3)
        ->and($softmaxResult[1])->toBeApproximately(0.242, 3)
        ->and($softmaxResult[2])->toBeApproximately(0.099, 3);

});

it('calculates softmax correctly for negative values', function () {
    $inputArray = [-2.0, -1.0, -0.1];
    $softmaxResult = Math::softmax($inputArray);

    expect($softmaxResult)->toBeArray()
        ->and($softmaxResult[0])->toBeApproximately(0.099, 3)
        ->and($softmaxResult[1])->toBeApproximately(0.242, 3)
        ->and($softmaxResult[2])->toBeApproximately(0.659, 3);
});

it('calculates logSoftmax correctly for positive values', function () {
    $inputArray = [2.0, 1.0, 0.1];
    $logSoftmaxResult = Math::logSoftmax($inputArray);

    expect($logSoftmaxResult)->toBeArray()
        ->and($logSoftmaxResult[0])->toBeApproximately(-0.417, 3)
        ->and($logSoftmaxResult[1])->toBeApproximately(-1.418, 3)
        ->and($logSoftmaxResult[2])->toBeApproximately(-2.303, 3);
});

it('calculates logSoftmax correctly for negative values', function () {
    $inputArray = [-2.0, -1.0, -0.1];
    $logSoftmaxResult = Math::logSoftmax($inputArray);

    expect($logSoftmaxResult)->toBeArray()
        ->and($logSoftmaxResult[0])->toBeApproximately(-2.303, 3)
        ->and($logSoftmaxResult[1])->toBeApproximately(-1.418, 3)
        ->and($logSoftmaxResult[2])->toBeApproximately(-0.417, 3);
});

it('gets top k items correctly', function () {
    $inputArray = [3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5];
    $topItems = Math::getTopItems($inputArray, 5);


    expect($topItems)->toBeArray()
        ->and($topItems)->toBe([
            [5, 9], [7, 6], [4, 5], [8, 5], [10, 5]
        ]);
});

it('gets all items when top k is 0', function () {
    $inputArray = [3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5];
    $topItems = Math::getTopItems($inputArray, 0);

    expect($topItems)->toBeArray()
        ->and($topItems)->toBe([
            [5, 9], [7, 6], [4, 5], [8, 5], [10, 5], [2, 4], [0, 3], [9, 3], [6, 2], [1, 1], [3, 1]
        ]);
});

it('gets top k items correctly from an associative array', function () {
    $inputArray = [
        'a' => 3,
        'b' => 1,
        'c' => 4,
        'd' => 1,
        'e' => 5,
        'f' => 9,
        'g' => 2,
        'h' => 6,
        'i' => 5,
        'j' => 3,
        'k' => 5,
    ];
    $topItems = Math::getTopItems($inputArray, 5);


    expect($topItems)->toBeArray()
        ->and($topItems)->toBe([
            ['f', 9], ['h', 6], ['e', 5], ['i', 5], ['k', 5]
        ]);
});


it('computes the Cartesian product correctly', function () {
    $inputArray = [
        [1, 2, 3],
        [4, 5, 6],
        [7, 8, 9]
    ];
    $cartesianProduct = Math::product(...$inputArray);

    expect($cartesianProduct)->toBeArray()
        ->and($cartesianProduct)->toBe([
            [1, 4, 7],
            [1, 4, 8],
            [1, 4, 9],
            [1, 5, 7],
            [1, 5, 8],
            [1, 5, 9],
            [1, 6, 7],
            [1, 6, 8],
            [1, 6, 9],
            [2, 4, 7],
            [2, 4, 8],
            [2, 4, 9],
            [2, 5, 7],
            [2, 5, 8],
            [2, 5, 9],
            [2, 6, 7],
            [2, 6, 8],
            [2, 6, 9],
            [3, 4, 7],
            [3, 4, 8],
            [3, 4, 9],
            [3, 5, 7],
            [3, 5, 8],
            [3, 5, 9],
            [3, 6, 7],
            [3, 6, 8],
            [3, 6, 9]
        ]);
});

it('computes the Cartesian product correctly for a single array', function () {
    $inputArray = [
        [1, 2, 3]
    ];
    $cartesianProduct = Math::product(...$inputArray);

    expect($cartesianProduct)->toBeArray()
        ->and($cartesianProduct)->toBe([
            [1],
            [2],
            [3]
        ]);
});

it('computes the Cartesian product correctly for an empty array', function () {
    $inputArray = [];
    $cartesianProduct = Math::product(...$inputArray);

    expect($cartesianProduct)->toBeArray()
        ->and($cartesianProduct)->toBe([[]]);
});


it('computes the Cartesian product correctly for an array with multiple empty arrays and one non-empty array', function () {
    $inputArray = [[], [], [1, 2, 3]];
    $cartesianProduct = Math::product(...$inputArray);

    expect($cartesianProduct)->toBeArray()
        ->and($cartesianProduct)->toBe([]);
});