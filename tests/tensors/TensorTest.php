<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\Tensor\Tensor;
use OutOfRangeException;

describe('Tensor creation', function () {
    it('can create tensor with various input types', function () {
        $t1 = new Tensor([1, 2, 3, 4]);
        expect($t1)->toBeInstanceOf(Tensor::class)
            ->and($t1->shape())->toBe([4])
            ->and($t1->dtype())->toBe(Tensor::float32);

        $t2 = new Tensor([[1, 2], [3, 4]]);
        expect($t2)->toBeInstanceOf(Tensor::class)
            ->and($t2->shape())->toBe([2, 2])
            ->and($t2->dtype())->toBe(Tensor::float32);

        $t3 = new Tensor(5, Tensor::int32);
        expect($t3)->toBeInstanceOf(Tensor::class)
            ->and($t3->shape())->toBe([])
            ->and($t3->dtype())->toBe(Tensor::int32);
    });

    it('can create ones tensor', function () {
        $t = Tensor::ones([2, 2], Tensor::int32);
        expect($t->toArray())->toBe([[1, 1], [1, 1]]);
    });

    it('can create zeros tensor', function () {
        $t = Tensor::zeros([2, 2], Tensor::int32);
        expect($t->toArray())->toBe([[0, 0], [0, 0]]);
    });

    it('can create a tensor from a binary string', function () {
        $binaryString = pack('g*', 1.0, 2.0, 3.0, 4.0);
        $t = Tensor::fromString($binaryString, Tensor::float32, [4]);
        expect($t->toArray())->toBe([1.0, 2.0, 3.0, 4.0]);
    });

    it('can create a tensor from repeating another tensor', function () {
        $t = new Tensor([1.0, 2.0, 3.0]);
        $repeated = Tensor::repeat($t, 3);
        expect($repeated->toArray())->toBe([1.0, 2.0, 3.0, 1.0, 2.0, 3.0, 1.0, 2.0, 3.0]);
    });

    test('Tensor properties are correctly set', function () {
        $t = new Tensor([1, 2, 3, 4]);
        expect($t->shape())->toBe([4])
            ->and($t->dtype())->toBe(Tensor::float32)
            ->and($t->ndim())->toBe(1)
            ->and($t->size())->toBe(4);
    });

    it('can be serialized and unserialized', function () {
        $t = new Tensor([1.0, 2.0, 3.0, 4.0]);
        $serialized = serialize($t);
        $unserialized = unserialize($serialized);
        expect($unserialized)->toBeInstanceOf(Tensor::class)
            ->and($unserialized->toArray())->toBe([1.0, 2.0, 3.0, 4.0]);
    });
});

describe('Mathematical Operations', function () {
    it('can reshape tensor', function () {
        $t = new Tensor([1, 2, 3, 4]);
        $reshaped = $t->reshape([2, 2]);
        expect($reshaped->shape())->toBe([2, 2]);
    });

    it('can perform element-wise addition', function () {
        $t1 = new Tensor([1.0, 2.0, 3.0]);
        $t2 = new Tensor([4.0, 5.0, 6.0]);
        $result = $t1->add($t2);
        expect($result->toArray())->toBe([5.0, 7.0, 9.0]);
    });

    it('can perform scalar addition', function () {
        $t = new Tensor([1.0, 2.0, 3.0]);
        $result = $t->add(5.0);
        expect($result->toArray())->toBe([6.0, 7.0, 8.0]);
    });

    it('can perform element-wise multiplication', function () {
        $t1 = new Tensor([1.0, 2.0, 3.0]);
        $t2 = new Tensor([4.0, 5.0, 6.0]);
        $result = $t1->multiply($t2);
        expect($result->toArray())->toBe([4.0, 10.0, 18.0]);
    });

    it('can perform scalar multiplication', function () {
        $t = new Tensor([1.0, 2.0, 3.0]);
        $result = $t->multiply(2.0);
        expect($result->toArray())->toBe([2.0, 4.0, 6.0]);
    });

    it('can calculate sigmoid', function () {
        $t = new Tensor([0, 1, -1]);
        $result = $t->sigmoid();
        expect($result->toArray())
            ->toMatchArrayApproximately([0.5, 0.7310585786300049, 0.2689414213699951]);
    });

    it('can calculate magnitude', function () {
        $t = new Tensor([3.0, 4.0]);
        expect($t->magnitude())->toBe(5.0);
    });

    it('can calculate dot product', function () {
        $t1 = new Tensor([1.0, 2.0, 3.0]);
        $t2 = new Tensor([4.0, 5.0, 6.0]);
        expect($t1->dot($t2))->toBe(32.0);
    });

    it('can calculate cross product', function () {
        $t1 = new Tensor([
            [1.0, 2.0, 3.0],
            [4.0, 5.0, 6.0],
            [7.0, 8.0, 9.0],
        ]);
        $t2 = new Tensor([
            [1.0, 2.0, 3.0],
            [4.0, 5.0, 6.0],
            [7.0, 8.0, 9.0],
        ]);

        expect($t1->cross($t2)->toArray())->toBe([
            [30.0, 36.0, 42.0],
            [66.0, 81.0, 96.0],
            [102.0, 126.0, 150.0],
        ]);
    });

    it('can calculate square root', function () {
        $t = new Tensor([4.0, 9.0]);
        expect($t->sqrt()->toArray())->toBe([2.0, 3.0]);
    });

    it('can calculate exponential', function () {
        $t = new Tensor([1.0, 2.0, 3.0]);
        $exp = $t->exp();
        expect($exp->toArray())
            ->toMatchArrayApproximately([2.718281828459045, 7.38905609893065, 20.085536923187668]);
    });

    it('can calculate log', function () {
        $t = new Tensor([1.0, 2.0, 3.0]);
        $log = $t->log();
        expect($log->toArray())
            ->toMatchArrayApproximately([0.0, 0.6931471805599453, 1.0986122886681098]);
    });

    it('can perform element-wise power', function () {
        $t1 = new Tensor([1.0, 2.0, 3.0]);
        $t2 = new Tensor([4.0, 5.0, 6.0]);
        $result = $t1->pow($t2);
        expect($result->toArray())->toBe([1.0, 32.0, 729.0]);
    });

    it('can perform scalar power', function () {
        $t = new Tensor([1.0, 2.0, 3.0]);
        $result = $t->pow(2.0);
        expect($result->toArray())->toBe([1.0, 4.0, 9.0]);
    });
});

describe('Tensor transformations', function () {
    it('can be transposed', function () {
        $t = new Tensor([[1.0, 2.0], [3.0, 4.0]]);
        $transposed = $t->transpose();
        expect($transposed->shape())->toBe([2, 2])
            ->and($transposed->toArray())->toBe([[1.0, 3.0], [2.0, 4.0]]);
    });

    it('can calculate reciprocal', function () {
        $t = new Tensor([1.0, 2.0, 3.0]);
        $reciprocal = $t->reciprocal();
        expect($reciprocal->toArray())->toMatchArrayApproximately([1.0, 0.5, 0.3333333333333333]);
    });

    it('can be squeezed', function () {
        $t = new Tensor([[[1], [2]], [[3], [4]]]);
        $squeezed = $t->squeeze(2);
        expect($squeezed->shape())->toBe([2, 2]);
    });

    it('can be unsqueezed', function () {
        $t = new Tensor([1, 2, 3, 4]);
        $unsqueezed = $t->unsqueeze(0);
        expect($unsqueezed->shape())->toBe([1, 4]);
    });

    it('can be stacked on axis 0', function () {
        $t1 = new Tensor([1.0, 2.0, 3.0]);
        $t2 = new Tensor([4.0, 5.0, 6.0]);
        $stacked = Tensor::stack([$t1, $t2], 0);
        expect($stacked->shape())->toBe([2, 3])
            ->and($stacked->toArray())->toBe([[1.0, 2.0, 3.0], [4.0, 5.0, 6.0]]);
    });

    it('can be stacked on axis 1', function () {
        $t1 = new Tensor([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $t2 = new Tensor([
            [5.0, 6.0],
            [7.0, 8.0],
        ]);
        $stacked = Tensor::stack([$t1, $t2], 1);
        expect($stacked->shape())->toBe([2, 2, 2])
            ->and($stacked->toArray())->toBe([[[1.0, 2.0], [5.0, 6.0]], [[3.0, 4.0], [7.0, 8.0]]]);
    });

    it('can be concatenated on axis 0', function () {
        $t1 = new Tensor([1.0, 2.0, 3.0]);
        $t2 = new Tensor([4.0, 5.0, 6.0]);
        $concatenated = Tensor::concat([$t1, $t2], 0);
        expect($concatenated->shape())->toBe([6])
            ->and($concatenated->toArray())->toBe([1.0, 2.0, 3.0, 4.0, 5.0, 6.0]);
    });

    it('can be concatenated on axis 1', function () {
        $t1 = new Tensor([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $t2 = new Tensor([
            [5.0, 6.0],
            [7.0, 8.0],
        ]);
        $concatenated = Tensor::concat([$t1, $t2], 1);
        expect($concatenated->shape())->toBe([2, 4])
            ->and($concatenated->toArray())->toBe([[1.0, 2.0, 5.0, 6.0], [3.0, 4.0, 7.0, 8.0]]);
    });
});

describe('Indexing and Slicing', function () {
    it('supports integer indexing', function () {
        $t = new Tensor([1.0, 2.0, 3.0, 4.0]);
        expect($t[0])->toBe(1.0)
            ->and($t[3])->toBe(4.0);
    });

    it('supports range indexing', function () {
        $t = new Tensor([1.0, 2.0, 3.0, 4.0, 5.0]);
        $slice = $t[[1, 3]];
        expect($slice->toArray())->toBe([2.0, 3.0]);
    });
});

describe('Statistical operations', function () {
    it('can calculate sum', function () {
        $t = new Tensor([1.0, 2.0, 3.0, 4.0, 5.0]);
        expect($t->sum())->toBe(15.0);
    });

    it('can calculate mean', function () {
        $t = new Tensor([1.0, 2.0, 3.0, 4.0, 5.0]);
        expect($t->mean())->toBe(3.0);
    });

    it('can calculate standard deviation', function () {
        $t = new Tensor([1.0, 2.0, 3.0, 4.0, 5.0]);
        [$std, $mean] = $t->stdMean();

        expect($std)->toEqualWithDelta(1.4142, 1e-4)
            ->and($mean)->toEqualWithDelta(3.0, 1e-4);
    })->todo();

    it('can calculate cosine similarity', function () {
        $t1 = new Tensor([1.0, 2.0, 3.0]);
        $t2 = new Tensor([4.0, 5.0, 6.0]);
        expect($t1->cosSimilarity($t2))->toEqualWithDelta(0.9746, 1e-4);
    });

    it('can perform softmax', function () {
        $t = new Tensor([1, 2, 3]);
        $result = $t->softmax();
        expect($result->toArray())->toMatchArrayApproximately([0.0900305, 0.2447284, 0.6652409], 1e-6)
            ->and($result->sum())->toEqualWithDelta(1, 1e-6);
    });

    it('can find top k values', function () {
        $t = new Tensor([1.0, 4.0, 3.0, 2.0, 5.0]);
        [$values, $indices] = $t->topk(3);
        expect($values->toArray())->toBe([5.0, 4.0, 3.0])
            ->and($indices->toArray())->toBe([4, 1, 2]);
    });
});

describe('Error handling', function () {
    it('throws exception for out of bounds indexing', function () {
        $t = new Tensor([1, 2, 3]);
        expect(fn () => $t[3])->toThrow(OutOfRangeException::class);
    });
});
