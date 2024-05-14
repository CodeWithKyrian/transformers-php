<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Tensor\TensorBuffer;

beforeEach(function () {
    $this->tensorBuffer = new TensorBuffer(5, Tensor::float32);
});

it('throws an exception when accessing offset with invalid type', fn() => $this->tensorBuffer['offset'])
    ->throws(TypeError::class);

it('can create a zero-sized buffer', function () {
    $buffer = new TensorBuffer(0, Tensor::float32);

    expect($buffer->count())->toBe(0);
});

it('gets the correct value at the given offset using square brackets', function () {
    expect($this->tensorBuffer[0])->toBe(0.0)
        ->and($this->tensorBuffer[4])->toBe(0.0);
});

it('sets the value at the given offset using square brackets', function () {
    $this->tensorBuffer[0] = 1.5;
    $this->tensorBuffer[4] = 2.5;

    expect($this->tensorBuffer[0])->toBe(1.5)
        ->and($this->tensorBuffer[4])->toBe(2.5);
});

it('throws an exception when accessing out-of-range offset', fn() => $this->tensorBuffer[5])
    ->throws(OutOfRangeException::class);

it('throws an exception when unsetting offset using square brackets', function () {
    unset($this->tensorBuffer[0]);
})->throws(LogicException::class);
