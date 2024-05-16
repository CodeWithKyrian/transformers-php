<?php

declare(strict_types=1);

namespace Tests\Utils;

use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;

/**
 * TODO
 */
it('truncateHelper ignores invalid array values', function () {
    // build dummy variable to pass the constructor without raising an error
    $tokenizerJSON = [
        'model' => [
            'type' => '__test',
            'vocab' => [
                '<s>' => 0,
            ],
        ],
    ];

    $subjectUnderTest = new PretrainedTokenizer($tokenizerJSON, []);

    $itemArray = [
        'foo' => [0, 1],
        'bar' => null
    ];

    // without the fix, it would lead to the following error:
    // array_slice(): Argument #1 ($array) must be of type array, null given
    $subjectUnderTest->truncateHelper($itemArray, 1024);
});