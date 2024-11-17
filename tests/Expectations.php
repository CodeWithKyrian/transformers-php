<?php

use Pest\Exceptions\InvalidExpectation;
use Pest\Exceptions\InvalidExpectationValue;

expect()->extend('toMatchArrayApproximately', function (array $expected, float $precision = 0.0001) {
    $actual = $this->value;

    expect($actual)
        ->toBeArray()
        ->and(count($actual))
        ->toBe(count($expected))
        ->and($actual)
        ->toHaveKeys(array_keys($expected));

    foreach ($expected as $key => $expectedValue) {
        $actualValue = $actual[$key];

        if (is_numeric($actualValue))
        {
            $message = "Failed asserting that $actualValue at key $key ≈ $expectedValue (±$precision)";
            expect($actualValue)
                ->toEqualWithDelta($expectedValue, $precision, $message);
        } else
        {
            $message = "Failed asserting that $actualValue at key $key ≈ $expectedValue";
            expect($actualValue)
                ->toEqual($expectedValue, $message);
        }
    }

    return $this;
});
