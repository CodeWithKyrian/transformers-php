<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use ArrayAccess;
use ArrayObject;
use Countable;
use EmptyIterator;
use Exception;
use Interop\Polite\Math\Matrix\NDArray;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use OutOfRangeException;
use Rindow\Math\Matrix\MatrixOperator;
use Rindow\Math\Matrix\OpenBlasBuffer;
use RuntimeException;
use Serializable;
use SplFixedArray;
use Traversable;

class Tensor implements NDArray, Countable, Serializable, IteratorAggregate
{
    protected static MatrixOperator $mo;
    protected array $shape;
    protected int $offset;
    protected int $dtype;
    protected SplFixedArray|OpenBlasBuffer $buffer;
    protected bool $portableSerializeMode = false;

    public function __construct($array = null, int $dtype = null, array $shape = null, int $offset = null)
    {
        if ($dtype === null) {
            if (is_bool($array)) {
                $dtype = NDArray::bool;
            } else {
                $dtype = NDArray::float32;
            }
        }

        if (is_array($array) || $array instanceof ArrayObject) {
            $size = $this->countRecursive($array);
            $this->buffer = $this->newBuffer($size, $dtype);
            $index = 0;
            $this->flattenArray($array, $this->buffer, $index);
            $this->offset = 0;
            $shape = $shape ?? $this->generateShape($array);
        } elseif (is_numeric($array) || is_bool($array)) {
            if (is_bool($array) && $dtype != NDArray::bool) {
                throw new InvalidArgumentException("Unmatched dtype with bool value");
            }
            $this->buffer = $this->newBuffer(1, $dtype);
            $this->buffer[0] = $array;
            $this->offset = 0;
            $shape = $shape ?? [];
            $this->assertShape($shape);
            if (array_product($shape) != 1)
                throw new InvalidArgumentException("Invalid dimension size");
        } elseif ($array === null && $shape !== null) {
            $this->assertShape($shape);
            $size = (int)array_product($shape);
            $this->buffer = $this->newBuffer($size, $dtype);
            $this->offset = 0;
        } elseif ($this->isBuffer($array)) {
            if (!is_int($offset))
                throw new InvalidArgumentException("Must specify offset with the buffer");
            if ($shape === null)
                throw new InvalidArgumentException("Invalid dimension size");
            $this->buffer = $array;
            $this->offset = $offset;
        } else {
            throw new InvalidArgumentException("Invalid type of array");
        }
        $this->assertShape($shape);
        $this->shape = $shape;

        $size = (int)array_product($shape);
        if (count($this->buffer) - $this->offset < $size)
            throw new InvalidArgumentException("Invalid dimension size");

        $this->dtype = $dtype;
    }

    function countRecursive($array): int
    {
        $count = 0;

        foreach ($array as $child) {
            if (is_array($child) || $child instanceof ArrayObject) {
                $count += $this->countRecursive($child);
            } else {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Create a new buffer for the tensor.
     *
     * @param int $size The size of the buffer.
     * @param int $dtype The data type of the buffer.
     * @return SplFixedArray|OpenBlasBuffer
     */
    protected function newBuffer(int $size, int $dtype): SplFixedArray|OpenBlasBuffer
    {
        if (extension_loaded('rindow_openblas')) {
            return new OpenBlasBuffer($size, $dtype);
        } else {
            return new SplFixedArray($size);
        }
    }

    /**
     * Flatten the given nested array into a flat array.
     */
    protected function flattenArray(array|ArrayObject $nestedArray, $flatArray, int &$currentIndex): int
    {
        $num = null;
        $cursor = 0;
        $nestedArrayLength = count($nestedArray);

        while ($cursor < $nestedArrayLength) {
            $value = $nestedArray[$cursor];
            if (is_array($value) || $value instanceof ArrayObject) {
                if ($value instanceof ArrayObject) {
                    $value = $value->getArrayCopy();
                }
                $num2 = $this->flattenArray($value, $flatArray, $currentIndex);
                if ($num === null) {
                    $num = $num2;
                } elseif ($num !== $num2) {
                    throw new InvalidArgumentException("The shape of the dimension is broken");
                }
            } else {
                if ($num !== null) {
                    throw new InvalidArgumentException("The shape of the dimension is broken");
                }

                $flatArray[$currentIndex] = $value;
                $currentIndex++;
            }
            $cursor++;
        }
        return $nestedArrayLength;
    }

    /**
     * Generate the shape of the given array.
     */
    protected function generateShape($array): array
    {
        $shape = [];

        while (is_array($array) || $array instanceof ArrayObject) {
            $shape[] = count($array);
            $array = $array[0];
        }

        return $shape;
    }

    /**
     * Assert that the given shape is valid.
     */
    protected function assertShape(array $shape): void
    {
        foreach ($shape as $num) {
            if (!is_int($num)) {
                throw new InvalidArgumentException(
                    "Invalid shape numbers. It gives " . gettype($num));
            }
            if ($num <= 0) {
                throw new InvalidArgumentException(
                    "Invalid shape numbers. It gives " . $num);
            }
        }
    }

    /**
     * Check if the given value is a buffer.
     */
    protected function isBuffer(mixed $buffer): bool
    {
        return $buffer instanceof SplFixedArray || $buffer instanceof OpenBlasBuffer;
    }

    /**
     * Return a zero matrix like the given one.
     *
     * @param Tensor $other The tensor to copy the shape and dtype from.
     */
    public static function zerosLike(Tensor $other): static
    {
        $mo = self::getMo();

        $ndArray = $mo->zerosLike($other);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    public static function getMo(): MatrixOperator
    {
        if (!isset(self::$mo)) {
            self::$mo = new MatrixOperator();
        }

        return self::$mo;
    }

    /**
     * Return the internal flat buffer of the tensor.
     */
    public function buffer(): ArrayAccess
    {
        return $this->buffer;
    }

    /**
     * Returns the data type of the tensor.
     */
    public function dtype(): ?int
    {
        return $this->dtype;
    }

    /**
     * Get the shape of the tensor.
     */
    public function shape(): array
    {
        return $this->shape;
    }

    /**
     * The offset of the tensor. This is used when the tensor is a view of another tensor.
     */
    public function offset(): int
    {
        return $this->offset;
    }

    /**
     * Return a one matrix like the given one.
     *
     * @param Tensor $other The tensor to copy the shape and dtype from.
     */
    public static function onesLike(Tensor $other): static
    {
        $mo = self::getMo();

        $ndArray = $mo->ones($other->shape, $other->dtype);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a one matrix with the given dimensions.
     *
     * @param array $shape The shape of the one matrix to return.
     * @param string|null $dtype The data type of the one matrix to return. Eg: float32, int32, etc. If null, defaults to float32.
     * @return static
     */
    public static function ones(array $shape, ?string $dtype = null): static
    {
        $mo = self::getMo();

        $ndArray = $mo->ones($shape, $dtype);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Reshapes a 1-dimensional array into an n-dimensional array, according to the provided dimensions.
     *
     * @param array $data The data to reshape.
     * @param array $shape The new shape of the array.
     *
     */
    public static function reshapeArray(array $data, array $shape): Tensor
    {
        $ndArray = self::fromArray($data);

        $ndArray = $ndArray->reshape($shape);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    public static function fromArray(array|NDArray $array, ?string $dtype = null, $shape = null): ?static
    {
        if (empty($array)) return null;

        if ($array instanceof NDArray) {
            return new static($array->buffer(), $array->dtype(), $shape ?? $array->shape(), $array->offset());
        }

        return new static($array, $dtype, $shape);
    }

    /**
     * Reshape the tensor into the given shape.
     */
    public function reshape(array $shape): NDArray
    {
        $this->assertShape($shape);

        if ($this->size() != array_product($shape)) {
            throw new InvalidArgumentException("Unmatched size to reshape: " .
                "[" . implode(',', $this->shape()) . "]=>[" . implode(',', $shape) . "]");
        }

        return new self($this->buffer(), $this->dtype(), $shape, $this->offset());
    }

    /**
     * Returns the total number of elements in the tensor.
     */
    public function size(): int
    {
        return (int)array_product($this->shape);
    }

    /**
     * Stack an array of tensors along a specified dimension.
     *
     * @param Tensor[] $tensors The array of tensors to stack.
     * @param int $dim The dimension to stack along.
     *
     * @return Tensor The stacked tensor.
     */
    public static function stack(array $tensors, int $dim = 0): Tensor
    {
        // TODO: Perform validation of shapes
        // NOTE: stack expects each tensor to be equal size
        return self::cat(array_map(fn($t) => $t->unsqueeze($dim), $tensors), $dim);
    }

    /**
     * Concatenates an array of tensors along a specified dimension.
     *
     * @param Tensor[] $tensors The array of tensors to concatenate.
     * @param int $dim The dimension to concatenate along.
     *
     * @return Tensor The concatenated tensor.
     * @throws Exception
     */
    public static function cat(array $tensors, int $dim = 0): Tensor
    {
        $dim = self::safeIndex($dim, $tensors[0]->ndim());

        // TODO: Perform validation of shapes

        $resultShape = $tensors[0]->shape();
        $resultOffset = $tensors[0]->offset();
        $resultType = $tensors[0]->dtype();
        $resultShape[$dim] = array_reduce($tensors, fn($carry, $tensor) => $carry + $tensor->shape()[$dim], 0);

        // Create a new array to store the accumulated values
        $resultSize = array_product($resultShape);

        $result = $tensors[0]->newBuffer($resultSize, $resultType);

        // Create output tensor of same type as first

        if ($dim === 0) {
            // Handle special case for performance reasons

            $offset = 0;
            foreach ($tensors as $t) {
                for ($i = 0; $i < $t->buffer->count(); $i++) {
                    $result[$offset++] = $t->buffer()[$i];
                }
            }
        } else {
            $currentDim = 0;

            foreach ($tensors as $tensor) {
                for ($i = 0; $i < $tensor->buffer->count(); $i++) {
                    $resultIndex = 0;

                    for ($j = $tensor->ndim() - 1, $num = $i, $resultMultiplier = 1; $j >= 0; --$j) {
                        $size = $tensor->shape()[$j];
                        $index = $num % $size;
                        if ($j === $dim) {
                            $index += $currentDim;
                        }
                        $resultIndex += $index * $resultMultiplier;
                        $resultMultiplier *= $resultShape[$j];
                        $num = (int)floor($num / $size);
                    }
                    $result[$resultIndex] = $tensor->buffer()[$i];
                }

                $currentDim += $tensor->shape()[$dim];
            }
        }

        return new Tensor($result, $resultType, $resultShape, $resultOffset);
    }

    /**
     * Safely calculates the positive index within the specified size and dimension.
     * @param int $index The input index.
     * @param int $size The size of the dimension.
     * @param int|null $dimension The dimension (optional).
     * @return int The positive index within bounds.
     * @throws Exception If the index is out of bounds.
     */
    protected static function safeIndex(int $index, int $size, ?int $dimension = null): int
    {
        if ($index < -$size || $index >= $size) {
            throw new Exception("IndexError: index $index is out of bounds for dimension"
                . ($dimension === null ? '' : ' ' . $dimension) . " with size $size"
            );
        }

        if ($index < 0) {
            // Negative indexing, ensuring positive index
            $index = (($index % $size) + $size) % $size;
        }

        return $index;
    }

    /**
     * Returns how many dimensions the tensor has.
     * @return int
     */
    public function ndim(): int
    {
        return count($this->shape);
    }

    public function count(): int
    {
        if (count($this->shape) == 0)
            return 0;

        return $this->shape[0];
    }

    /**
     * Returns a tensor with all specified dimensions of input of size 1 removed.
     *
     * @param ?int $dim If given, the input will be squeezed only in the specified dimensions.
     *
     * @return static The squeezed tensor.
     */
    public function unsqueeze(?int $dim = null): static
    {
        return new Tensor(
            $this->buffer(),
            $this->dtype,
            $this->calcUnsqueezeDims($this->shape(), $dim),
            $this->offset
        );
    }

    /**
     * Helper function to calculate new dimensions when performing an unsqueeze operation.
     * @param array $dims The dimensions of the tensor.
     * @param int $dim The dimension to unsqueeze.
     * @return array The new dimensions.
     */
    protected function calcUnsqueezeDims(array $dims, int $dim): array
    {
        // Dimension out of range (e.g., "expected to be in range of [-4, 3], but got 4")
        // + 1 since we allow inserting at the end (i.e. dim = -1)
        $dim = self::safeIndex($dim, count($dims) + 1);
        $newDims = $dims;
        // Insert 1 into specified dimension
        array_splice($newDims, $dim, 0, [1]);
        return $newDims;
    }

    /**
     * Add two NDArrays element-wise, A + B
     *
     * @param Tensor $other The NDArray to add to this NDArray.
     * @return static
     */
    public function add(Tensor $other): static
    {
        $mo = self::getMo();

        $ndArray = $mo->add($this, $other);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a new Tensor with every element added by a constant.
     *
     * @param float|int $scalar The constant to add.
     * @return static
     */
    public function addScalar(float|int $scalar): static
    {
        $mo = self::getMo();

        $ndArray = $mo->op($this, '+', $scalar);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a new Tensor with the sigmoid function applied to each element.
     * @return self
     */
    public function sigmoid(): self
    {
        $mo = self::getMo();

        $ndArray = $mo->f(fn($x) => 1 / (1 + exp(-$x)), $this);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a new Tensor with every element multiplied by a constant.
     *
     * @param float|int $scalar The constant to multiply by.
     *
     * @return self
     */
    public function multiplyScalar(float|int $scalar): self
    {
        $mo = self::getMo();

        $ndArray = $mo->la()->scal($scalar, $this);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a transposed version of this Tensor.
     * @return $this
     */
    public function transpose(): self
    {
        $mo = self::getMo();

        $ndArray = $mo->transpose($this);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Performs `L_p` normalization of inputs over specified dimension.
     *
     * @param int $p Order of the norm. Supported values are 1, 2, Infinity.
     * @param int|null $dim The axis or axes along which to perform the reduction. If null (default), reduces all dimensions.
     *
     * @return static The normalized tensor.
     */
    public function normalize(int $p = 2, ?int $dim = null): static
    {
        $mo = self::getMo();

        $result = clone $this;

        $dim = $result->safeIndex($dim, $result->ndim());

        $norm = $result->norm($p, $dim, true);

        foreach ($norm->buffer as $i => $value) {
            $resultIndex = 0;
            $num = $i;
            $resultMultiplier = 1;

            for ($j = $result->ndim() - 1; $j >= 0; --$j) {
                $size = $result->shape()[$j];

                if ($j !== $dim) {
                    $index = $num % $size;
                    $resultIndex += $index * $resultMultiplier;
                    $resultMultiplier *= $result->shape()[$j];
                }

                $num = floor($num / $size);
            }

            // Divide by normalized value
            $result->buffer[$i] /= $norm->buffer[$resultIndex];
        }

        return $result;
    }

    /**
     * Returns the matrix norm or vector norm of a given tensor.
     *
     * @param int $ord Order of the norm. Supported values are 1, 2, Infinity.
     * @param int|null $dim The axis or axes along which to perform the reduction. If null (default), reduces all dimensions.
     * @param bool $keepdims If true, retains reduced dimensions with length 1.
     *
     * @return static
     */
    public function norm(int $ord = 2, ?int $dim = null, bool $keepdims = false): static
    {
        $mo = self::getMo();

        if ($dim === null) {
            $val = pow(array_reduce($this->buffer, function ($carry, $item) use ($ord) {
                return $carry + pow($item, $ord);
            }, 0), 1 / $ord);

            return new Tensor([$val], $this->dtype(), []);
        }

        // Negative indexing
        $dim = $this->safeIndex($dim, $this->ndim());

        // Calculate the shape of the resulting array after summation
        $resultDims = $this->shape();
        $resultDims[$dim] = 1; // Remove the specified axis

        // Create a new array to store the accumulated values
        $result = $this->zeros([count($this->buffer) / $this->shape()[$dim]]);

        // Iterate over the data array
        foreach ($this->buffer as $i => $value) {
            // Calculate the index in the resulting array
            $resultIndex = 0;
            $num = $i;
            $resultMultiplier = 1;

            for ($j = $this->ndim() - 1; $j >= 0; --$j) {
                $size = $this->shape()[$j];

                if ($j !== $dim) {
                    $index = $num % $size;
                    $resultIndex += $index * $resultMultiplier;
                    $resultMultiplier *= $resultDims[$j];
                }

                $num = floor($num / $size);
            }

            // Accumulate the value at the current index
            $result[$resultIndex] += pow($this->buffer[$i], $ord);
        }

        if ($ord === 1) {
            $result = $mo->op($result, '**', 1 / $ord);
        }

        if (!$keepdims) {
            array_splice($resultDims, $dim, 1);
        }

        return new static($result->buffer(), $result->dtype(), $resultDims, $result->offset());
    }

    /**
     * Return a zero matrix with the given dimensions.
     * @param array $shape The shape of the zero matrix to return.
     * @param string|null $dtype The data type of the zero matrix to return. Eg: float32, int32, etc. If null, defaults to float32.
     * @return static
     */
    public static function zeros(array $shape, ?string $dtype = null): static
    {
        $mo = self::getMo();

        $ndArray = $mo->zeros($shape, $dtype);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Returns a tensor with all specified dimensions of input of size 1 removed.
     *
     * @param ?int $dim If given, the input will be squeezed only in the specified dimensions.
     *
     * @return static The squeezed tensor.
     */
    public function squeeze(?int $dim = null): static
    {
        $mo = self::getMo();

        $ndArray = $mo->la()->squeeze($this, $dim);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Clamps all elements in input into the range [ min, max ] and returns a resulting tensor.
     *
     * @param float|int $min The minimum value.
     * @param float|int $max The maximum value.
     * @return static The clamped tensor.
     */
    public function clamp(float|int $min, float|int $max): static
    {
        $mo = self::getMo();

        $result = $mo->f(fn($x) => max($min, min($max, $x)), $this);

        return new static($result->buffer(), $result->dtype(), $result->shape(), $result->offset());
    }

    /**
     * Rounds elements of input to the nearest integer.
     * @return static The rounded tensor.
     */
    public function round(): static
    {
        $mo = self::getMo();

        $result = $mo->f(fn($x) => round($x), $this);

        return new static($result->buffer(), $result->dtype(), $result->shape(), $result->offset());
    }

    /**
     * Performs Tensor dtype conversion.
     *
     * @param int $dtype The target data type.
     * @return static The converted tensor.
     */
    public function to(int $dtype): static
    {
        if ($this->dtype() === $dtype) {
            return $this;
        }

        $mo = self::getMo();

        $ndArray = $mo->astype($this, $dtype);

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Returns the mean value of each row of the tensor in the given dimension dim.
     */
    public function mean(?int $dim = null, bool $keepdims = false): static
    {
        $mo = self::getMo();

        $ndArray = $mo->mean($this, $dim);

        if (!$keepdims) {
            array_splice($ndArray->shape, $dim, 1);
        }

        return new static($ndArray->buffer(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Perform mean pooling of the tensor followed by a normalization step.
     *
     * @param Tensor $other The tensor to pool of the same shape as the input tensor.
     *
     * @return Tensor The pooled tensor.
     */
    public function meanPooling(Tensor $other): Tensor
    {
        // $this->shape should be : [batchSize, seqLength, embedDim]
        // $other->shape should be : [batchSize, seqLength]
        [$batchSize, $seqLength, $embedDim] = $this->shape();

        $returnedData = [];
        $outIndex = 0;

        for ($i = 0; $i < $batchSize; ++$i) {
            $offset = $i * $embedDim * $seqLength;

            for ($k = 0; $k < $embedDim; ++$k) {
                $sum = 0;
                $count = 0;

                $otherOffset = $i * $seqLength;
                $offset2 = $offset + $k;

                // Pool over all words in sequence
                for ($j = 0; $j < $seqLength; ++$j) {
                    // index into attention mask
                    $attn = (int)$other[$i][$j];

                    $count += $attn;
                    $sum += $this[$i][$j][$k] * $attn;
                }

                $avg = $count ? $sum / $count : 0;
                $returnedData[$outIndex++] = $avg;
            }
        }

        return new Tensor($returnedData, $this->dtype(), [$batchSize, $embedDim]);
    }

    public function slice(...$slices): Tensor
    {
        $newTensorDims = [];
        $newOffsets = [];

        for ($sliceIndex = 0; $sliceIndex < $this->ndim(); ++$sliceIndex) {
            $slice = $slices[$sliceIndex] ?? null;

            if ($slice === null) {
                $newOffsets[] = [0, $this->shape()[$sliceIndex]];
                $newTensorDims[] = $this->shape()[$sliceIndex];

            } elseif (is_int($slice)) {
                $slice = $this->safeIndex($slice, $this->shape()[$sliceIndex], $sliceIndex);
                $newOffsets[] = [$slice, $slice + 1];

            } elseif (is_array($slice) && count($slice) === 2) {
                if ($slice[0] > $slice[1]) {
                    throw new Exception("Invalid slice: " . json_encode($slice));
                }
                $offsets = [
                    max($slice[0], 0),
                    min($slice[1], $this->shape()[$sliceIndex])
                ];
                $newOffsets[] = $offsets;
                $newTensorDims[] = $offsets[1] - $offsets[0];

            } else {
                throw new Exception("Invalid slice: " . json_encode($slice));
            }
        }

        $newDims = array_map(fn($offsets) => $offsets[1] - $offsets[0], $newOffsets);

        $newBufferSize = array_reduce($newDims, fn($a, $b) => $a * $b, 1);

        $buffer = $this->newBuffer($newBufferSize, $this->dtype());
        $stride = $this->stride();

        for ($i = 0; $i < $newBufferSize; ++$i) {
            $originalIndex = 0;
            for ($j = count($newDims) - 1, $num = $i; $j >= 0; --$j) {
                $size = $newDims[$j];
                $originalIndex += (($num % $size) + $newOffsets[$j][0]) * $stride[$j];
                $num = floor($num / $size);
            }
            $buffer[$i] = $this->buffer[$originalIndex];
        }

        return new Tensor($buffer, $this->dtype(), $newDims, $this->offset());
    }

    /**
     * Compute and return the stride of this tensor.
     * Stride is the jump necessary to go from one element to the next one in the specified dimension dim.
     * @return array The stride of this tensor.
     */
    public function stride(): array
    {
        $stride = [];
        $s2 = 1;

        for ($i = $this->ndim() - 1; $i >= 0; --$i) {
            $stride[$i] = $s2;
            $s2 *= $this->shape()[$i];
        }

        return array_reverse($stride, true);
    }


    /**
     * Permutes a tensor according to the provided axes.
     * @param array $axes The axes to permute the tensor along.
     * @return Tensor The permuted tensor.
     */
    public function permute(...$axes): static
    {
        [$permutedData, $shape] = Math::permuteData($this->toBufferArray(), $this->shape(), $axes);

        return new Tensor($permutedData, $this->dtype(), $shape);
    }

    /**
     * Convert the tensor into a flat array of the buffer contents.
     */
    public function toBufferArray()
    {
        if ($this->buffer instanceof OpenBlasBuffer) {
            return $this->buffer->dump();
        } elseif ($this->buffer instanceof SplFixedArray) {
            return $this->buffer->toArray();
        } else {
            throw new RuntimeException('Unknown buffer type is inconvertible:' . get_class($this->buffer));
        }
    }

    /**
     * Convert the tensor into an array.
     */
    public function toArray()
    {
        if (count($this->shape) == 0) {
            return $this->buffer[$this->offset];
        }

        $idx = $this->offset;

        return $this->unflattenArray($this->buffer, $idx, $this->shape);
    }

    /**
     * Unflatten the given flat array into a nested array according to the given shape.
     */
    protected function unflattenArray($flatArray, &$currentIndex, array $shape): array
    {
        $size = array_shift($shape);
        $nestedArray = [];

        if (count($shape)) {
            for ($i = 0; $i < $size; $i++) {
                $nestedArray[$i] = $this->unflattenArray($flatArray, $currentIndex, $shape);
            }
        } else {
            for ($i = 0; $i < $size; $i++) {
                $nestedArray[$i] = $flatArray[$currentIndex];
                $currentIndex++;
            }
        }
        return $nestedArray;
    }

    /**
     * Calculate the softmax of the tensor.
     *
     */
    public function softmax(): array
    {
        return Math::softmax($this->toArray());
    }

    public function max(?int $axis = null): static|int|float
    {
        $mo = self::getMo();

        $max = $mo->max($this, $axis);

        if ($max instanceof NDArray) {
            return new static($max->buffer(), $max->dtype(), $max->shape(), $max->offset());
        }

        return $max;
    }

    public function argMax(?int $axis = null): static|int|float
    {
        $mo = self::getMo();

        $argMax = $mo->argMax($this, $axis);

        if ($argMax instanceof NDArray) {
            return new static($argMax->buffer(), $argMax->dtype(), $argMax->shape(), $argMax->offset());
        }

        return $argMax;
    }


    public function offsetSet($offset, $value): void
    {
        if (!$this->offsetExists($offset))
            throw new OutOfRangeException("Index is out of range");

        // For range specification e.g. $tensor[1:3]
        if (is_array($offset)) {
            throw new OutOfRangeException("Unsupported to set for range specification.");
        }

        // For single index specification e.g. $tensor[1]
        $shape = $this->shape;

        $max = array_shift($shape);

        if (!count($shape)) {
            if (!is_scalar($value))
                throw new InvalidArgumentException("Must be scalar type");
            $this->buffer[$this->offset + $offset] = $value;
            return;
        }

        if (!($value instanceof self) || $value->shape() != $shape) {
            throw new InvalidArgumentException("Unmatched shape numbers");
        }
        $copy = $value->buffer();
        $size = (int)array_product($shape);
        $src_idx = $value->offset();
        $idx = $this->offset + $offset * $size;

        for ($i = 0; $i < $size; $i++, $idx++, $src_idx++) {
            $this->buffer[$idx] = $copy[$src_idx];
        }
    }

    public function offsetExists($offset): bool
    {
        if (count($this->shape) == 0)
            return false;

        if (is_array($offset)) {
            if (count($offset) != 2 ||
                !array_key_exists(0, $offset) || !array_key_exists(1, $offset) ||
                $offset[0] > $offset[1]) {
                $det = '';
                if (is_numeric($offset[0]) && is_numeric($offset[1]))
                    $det = ':[' . implode(',', $offset) . ']';
                throw new OutOfRangeException("Illegal range specification." . $det);
            }
            $start = $offset[0];
            $end = $offset[1];
        } elseif (is_int($offset)) {
            $start = $offset;
            $end = $offset;
        } else {
            throw new OutOfRangeException("Dimension must be integer");
        }

        if ($start < 0 || $end >= $this->shape[0])
            return false;

        return true;
    }

    public function offsetUnset($offset): void
    {
        throw new LogicException("Unsupported Operation");
    }

    public function getIterator(): Traversable
    {
        if (count($this->shape) == 0)
            return new EmptyIterator();

        $count = $this->shape[0];

        for ($i = 0; $i < $count; $i++) {
            yield $i => $this->offsetGet($i);
        }
    }

    public function offsetGet($offset): mixed
    {
        if (!$this->offsetExists($offset))
            throw new OutOfRangeException("Index is out of range");

        // For range specification e.g. $tensor[1:3]
        if (is_array($offset)) {
            $shape = $this->shape;
            array_shift($shape);
            $rowsCount = $offset[1] - $offset[0] + 1;

            $itemSize = count($shape) > 0 ? (int)array_product($shape) : 1;

            if ($rowsCount < 0) {
                throw new OutOfRangeException('Invalid range');
            }

            array_unshift($shape, $rowsCount);
            $size = (int)array_product($shape);

            return new self($this->buffer, $this->dtype, $shape, $this->offset + $offset[0] * $itemSize);
        }

        // For single index specification e.g. $tensor[1]
        $shape = $this->shape;
        $max = array_shift($shape);

        if (count($shape) == 0) {
            return $this->buffer[$this->offset + $offset];
        }

        $size = (int)array_product($shape);

        return new self($this->buffer, $this->dtype, $shape, $this->offset + $offset * $size);
    }

    public function getPortableSerializeMode(): bool
    {
        return $this->portableSerializeMode;
    }

    public function setPortableSerializeMode(bool $mode): void
    {
        $this->portableSerializeMode = $mode;
    }

    public function serialize(): ?string
    {
        // Never called at the time of serialization.
        // Interface for convenience.
        return serialize($this->__serialize());
    }

    public function __serialize()
    {
        if (extension_loaded('rindow_openblas')) {
            if (!$this->portableSerializeMode) {
                return [
                    'm' => 'rindow_openblas',
                    's' => $this->shape,
                    'o' => $this->offset,
                    't' => $this->dtype,
                    'z' => count($this->buffer),
                    'b' => $this->buffer->dump()
                ];
            }
            $count = count($this->buffer);
            $array = [];
            for ($i = 0; $i < $count; $i++) {
                $array[$i] = $this->buffer[$i];
            }
            return [
                'm' => 'linear-array',
                's' => $this->shape,
                'o' => $this->offset,
                't' => $this->dtype,
                'z' => count($this->buffer),
                'b' => $array
            ];

        } else {
            return [
                'm' => 'linear-array',
                's' => $this->shape,
                'o' => $this->offset,
                't' => $this->dtype,
                'z' => count($this->buffer),
                'b' => $this->buffer->toArray()
            ];
        }
    }

    public function unserialize($data): void
    {
        // Never called at the time of unserialization.
        // Interface for convenience.
        $this->__unserialize(unserialize($data));
    }

    public function __unserialize($data)
    {
        $mode = $data['m'];
        $this->shape = $data['s'];
        $this->offset = $data['o'];
        $this->dtype = $data['t'];
        if ($mode == 'rindow_openblas') {
            if (!extension_loaded('rindow_openblas')) {
                throw new RuntimeException('"rindow_openblas" extension is not loaded.');
            }
            $this->buffer = new OpenBlasBuffer($data['z'], $data['t']);
            $this->buffer->load($data['b']);
        } elseif ($mode == 'linear-array') {
            if (!extension_loaded('rindow_openblas')) {
                $this->buffer = SplFixedArray::fromArray($data['b']);
                return;
            }
            $this->buffer = new OpenBlasBuffer($data['z'], $data['t']);
            foreach ($data['b'] as $key => $value) {
                $this->buffer[$key] = $value;
            }
        } else {
            throw new RuntimeException('Illegal save mode: ' . $mode);
        }
    }

    public function __clone()
    {
        if ($this->buffer instanceof OpenBlasBuffer) {
            $newBuffer = new OpenBlasBuffer(
                count($this->buffer), $this->buffer->dtype());
            $newBuffer->load($this->buffer->dump());
            $this->buffer = $newBuffer;
        } elseif ($this->buffer instanceof SplFixedArray) {
            $this->buffer = clone $this->buffer;
        } else {
            throw new RuntimeException('Unknown buffer type is uncloneable:' . get_class($this->buffer));
        }
    }

}