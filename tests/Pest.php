<?php

expect()->extend('toBeApproximately', function ($expected, $precision = 0) {
    $actual = $this->value;
    $this->value = round($actual, $precision) === round($expected, $precision);

    return $this;
});