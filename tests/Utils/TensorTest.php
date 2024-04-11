<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

it('can create a new Tensor', function () {
    $tensor = new Tensor([1, 2, 3, 4]);

    expect($tensor)->toBeInstanceOf(Tensor::class);
});

it('can create a new Tensor from a 2D array', function () {
    $tensor = Tensor::fromArray([[1, 2], [3, 4]]);

    expect($tensor)->toBeInstanceOf(Tensor::class)
        ->and($tensor->shape())->toBe([2, 2])
        ->and($tensor->toArray())->toBe([[1, 2], [3, 4]]);
});

it('can create a tensor of zeros', function () {
    $tensor = Tensor::zeros([2, 2]);

    expect($tensor)->toBeInstanceOf(Tensor::class)
        ->and($tensor->shape())->toBe([2, 2])
        ->and($tensor->toArray())->toBe([[0.0, 0.0], [0.0, 0.0]]);
});

it('can create a tensor of zeros like another tensor', function () {
    $tensor = Tensor::fromArray([[2, 4], [6, 8]]);

    $zerosTensor = Tensor::zerosLike($tensor);

    expect($zerosTensor)->toBeInstanceOf(Tensor::class)
        ->and($zerosTensor->shape())->toBe([2, 2])
        ->and($zerosTensor->toArray())->toBe([[0.0, 0.0], [0.0, 0.0]]);
});

it('can create a tensor of ones', function () {
    $tensor = Tensor::ones([2, 2]);

    expect($tensor)->toBeInstanceOf(Tensor::class)
        ->and($tensor->shape())->toBe([2, 2])
        ->and($tensor->toArray())->toBe([[1.0, 1.0], [1.0, 1.0]]);
});

it('can create a tensor of ones like another tensor', function () {
    $tensor = Tensor::fromArray([[2, 4], [6, 8]]);

    $zerosTensor = Tensor::onesLike($tensor);

    expect($zerosTensor)->toBeInstanceOf(Tensor::class)
        ->and($zerosTensor->shape())->toBe([2, 2])
        ->and($zerosTensor->toArray())->toBe([[1.0, 1.0], [1.0, 1.0]]);
});

it('can add two tensors element-wise', function () {
    $tensor1 = Tensor::fromArray([[1, 2], [3, 4]]);
    $tensor2 = Tensor::fromArray([[5, 6], [7, 8]]);
    $result = $tensor1->add($tensor2);

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([[6, 8], [10, 12]]);
});

it('can add a scalar to each element of a tensor', function () {
    $tensor = Tensor::fromArray([[1, 2], [3, 4]]);
    $result = $tensor->add(5);

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([[6, 7], [8, 9]]);
});

it('can apply the sigmoid function to each element of a tensor', function () {
    $tensor = Tensor::fromArray([[0, 1], [-1, 2]]);
    $result = $tensor->sigmoid();

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([[0.5, 0.7310585786300049], [0.2689414213699951, 0.8807970779778823]]);
});

it('can multiply each element of a tensor by a scalar', function () {
    $tensor = Tensor::fromArray([[1, 2], [3, 4]]);
    $result = $tensor->multiply(2);

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([[2.0, 4.0], [6.0, 8.0]]);
});

it('can compute the mean value of each row of the tensor', function () {
    $tensor = Tensor::fromArray([[1, 2], [3, 4]]);
    $result = $tensor->mean(axis: 1);

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([1.5, 3.5]);
});

it('can clamp all elements of the tensor within a specified range', function () {
    $tensor = Tensor::fromArray([[1, 2], [3, 4]]);
    $result = $tensor->clamp(2, 3);

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([[2, 2], [3, 3]]);
});

it('can round all elements of the tensor to the nearest integer', function () {
    $tensor = Tensor::fromArray([[1.2, 2.7], [3.5, 4.9]]);
    $result = $tensor->round();

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([[1.0, 3.0], [4.0, 5.0]]);
});

it('can perform mean pooling on a tensor', function () {
    $tensor = Tensor::fromArray([[[1, 2], [3, 4]], [[5, 6], [7, 8]]]);
    $attentionMask = Tensor::fromArray([[1, 0], [1, 1]]);

    $result = $tensor->meanPooling($attentionMask);

    expect($result)->toBeInstanceOf(Tensor::class)
        ->and($result->toArray())->toBe([[1, 2], [6, 7]]);
});

it('can slice a tensor based on provided slices', function () {

})->todo();

it('can compute the stride of a tensor', function () {
    $tensor = Tensor::fromArray([[[1, 2], [3, 4]], [[5, 6], [7, 8]]]);

    $result = $tensor->stride();

    expect($result)->toBeArray()->toBe([4, 2, 1]);
});