<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Rindow\Math\Matrix\MatrixOperator;
use Rindow\Math\Matrix\NDArrayPhp;

class Tensor extends NDArrayPhp
{

    public static function getMo(): MatrixOperator
    {
        return new MatrixOperator();
    }

    public static function fromArray(array $array, ?string $dtype = null): ?static
    {
        if (empty($array)) return null;

        return new static($array, $dtype);
    }

    public static function fromNdArray(NDArrayPhp $ndArray): static
    {
        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a one matrix like the given one.
     *
     * @param Tensor $other The tensor to copy the shape and dtype from.
     */
    public static function onesLike(Tensor $other): static
    {
        $mo = self::getMo();

        $ndArray = $mo->ones($other->shape(), $other->dtype());

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a new Tensor with the sigmoid function applied to each element.
     * @return self
     */
    public function sigmoid(): self
    {
        $mo = self::getMo();

        $ndArray = $mo->f(fn($x) => 1 / (1 + exp(-$x)), $this);

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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

        $ndArray = $mo->op($this, '*', $scalar);

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Return a transposed version of this Tensor.
     * @return $this
     */
    public function transpose(): self
    {
        $mo = self::getMo();

        $ndArray = $mo->transpose($this);

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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
            $val = pow(array_reduce($this->_buffer, function ($carry, $item) use ($ord) {
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
        $result = $this->zeros([count($this->_buffer) / $this->shape()[$dim]]);

        // Iterate over the data array
        foreach ($this->_buffer as $i => $value) {
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
            $result[$resultIndex] += pow($this->_buffer[$i], $ord);
        }

        if ($ord === 1) {
            $result = $mo->op($result, '**', 1 / $ord);
        }

        if (!$keepdims) {
            array_splice($resultDims, $dim, 1);
        }

        return new static($result->toArray(), $result->dtype(), $resultDims);
    }

    /**
     * Safely calculates the positive index within the specified size and dimension.
     * @param int $index The input index.
     * @param int $size The size of the dimension.
     * @param int|null $dimension The dimension (optional).
     * @return int The positive index within bounds.
     * @throws \Exception If the index is out of bounds.
     */
    protected static function safeIndex(int $index, int $size, ?int $dimension = null): int
    {
        if ($index < -$size || $index >= $size) {
            throw new \Exception("IndexError: index $index is out of bounds for dimension"
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

        foreach ($norm->_buffer as $i => $value) {
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
            $result->_buffer[$i] /= $norm->_buffer[$resultIndex];
        }

        return $result;
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

        $result = clone $this;

        if ($dim === null) {
            $result->_buffer = array_filter($result->_buffer, function ($value) {
                return $value !== 1;
            });
            $result->_shape = array_filter($result->_shape, function ($value) {
                return $value !== 1;
            });
        } else {
            $dim = $result->safeIndex($dim, $result->ndim());

            if ($result->_shape[$dim] !== 1) {
                throw new \Exception("DimensionError: cannot select an axis to squeeze out which has size not equal to one");
            }

            array_splice($result->_buffer, $dim, 1);
            array_splice($result->_shape, $dim, 1);
        }

        return $result;
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
     * Returns a tensor with all specified dimensions of input of size 1 removed.
     *
     * @param ?int $dim If given, the input will be squeezed only in the specified dimensions.
     *
     * @return static The squeezed tensor.
     */
    public function unsqueeze(?int $dim = null): static
    {
        return new Tensor(
            $this->_buffer->toArray(),
            $this->_dtype,
            $this->calcUnsqueezeDims($this->shape(), $dim),
        );
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

        return new static($result->toArray(), $result->dtype(), $result->shape(), $result->offset());
    }

    /**
     * Rounds elements of input to the nearest integer.
     * @return static The rounded tensor.
     */
    public function round(): static
    {
        $mo = self::getMo();

        $result = $mo->f(fn($x) => round($x), $this);

        return new static($result->toArray(), $result->dtype(), $result->shape(), $result->offset());
    }

    /**
     * Performs Tensor dtype conversion.
     *
     * @param string $dtype The target data type.
     * @return static The converted tensor.
     */
    public function to(string $dtype): static
    {
        if ($this->dtype() === $dtype) {
            return $this;
        }

        $mo = self::getMo();

        $ndArray = $mo->astype($this, $dtype);

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
    }

    /**
     * Returns the mean value of each row of the tensor in the given dimension dim.
     */
    public function mean(?int $dim = null, bool $keepdims = false): static
    {
        $mo = self::getMo();

        $ndArray = $mo->mean($this, $dim);

        if (!$keepdims) {
            array_splice($ndArray->_shape, $dim, 1);
        }

        return new static($ndArray->toArray(), $ndArray->dtype(), $ndArray->shape(), $ndArray->offset());
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
                    throw new \Exception("Invalid slice: " . json_encode($slice));
                }
                $offsets = [
                    max($slice[0], 0),
                    min($slice[1], $this->shape()[$sliceIndex])
                ];
                $newOffsets[] = $offsets;
                $newTensorDims[] = $offsets[1] - $offsets[0];

            } else {
                throw new \Exception("Invalid slice: " . json_encode($slice));
            }
        }

        $newDims = array_map(fn($offsets) => $offsets[1] - $offsets[0], $newOffsets);

        $newBufferSize = array_reduce($newDims, fn($a, $b) => $a * $b, 1);

        $buffer = [];
        $stride = $this->stride();

        for ($i = 0; $i < $newBufferSize; ++$i) {
            $originalIndex = 0;
            for ($j = count($newDims) - 1, $num = $i; $j >= 0; --$j) {
                $size = $newDims[$j];
                $originalIndex += (($num % $size) + $newOffsets[$j][0]) * $stride[$j];
                $num = floor($num / $size);
            }
            $buffer[$i] = $this->_buffer[$originalIndex];
        }

        return new Tensor($buffer, $this->dtype(), $newDims);
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

    protected function array2Flat($A, $F, &$idx, $prepare)
    {
//        if (is_array($A)) {
//            ksort($A);
//        } elseif ($A instanceof \ArrayObject) {
//            $A->ksort();
//        } else {
//            // If $A is neither an array nor an ArrayObject, it's an unexpected type.
//            throw new \InvalidArgumentException("Input must be an array or ArrayObject.");
//        }

        $num = null;
        $cursor = 0;
        $arrayLength = count($A); // Optimize count() call
        while ($cursor < $arrayLength) {
            $value = $A[$cursor];
            if (is_array($value) || $value instanceof \ArrayObject) {
                if ($value instanceof \ArrayObject) {
                    $value = $value->getArrayCopy(); // Standardize handling of ArrayObject
                }
                $num2 = $this->array2Flat($value, $F, $idx, $prepare);
                if ($num === null) {
                    $num = $num2;
                } elseif ($num !== $num2) {
                    throw new \InvalidArgumentException("The shape of the dimension is broken");
                }
            } else {
                if ($num !== null) {
                    throw new \InvalidArgumentException("The shape of the dimension is broken");
                }
                if (!$prepare) {
                    $F[$idx] = $value;
                }
                $idx++;
            }
            $cursor++;
        }
        return $arrayLength; // Use the pre-computed length
    }

    /**
     * Permutes a tensor according to the provided axes.
     * @param array $axes The axes to permute the tensor along.
     * @return Tensor The permuted tensor.
     */
    public function permute(...$axes): static
    {
        [$permutedData, $shape] = Math::permuteData($this->_buffer->toArray(), $this->shape(), $axes);

        return new Tensor($permutedData, $this->dtype(), $shape);
    }

    /**
     * Concatenates an array of tensors along a specified dimension.
     *
     * @param Tensor[] $tensors The array of tensors to concatenate.
     * @param int $dim The dimension to concatenate along.
     *
     * @return Tensor The concatenated tensor.
     * @throws \Exception
     */
    public static function cat(array $tensors, int $dim = 0): Tensor
    {
        $dim = self::safeIndex($dim, $tensors[0]->ndim());

        // TODO: Perform validation of shapes

        $resultShape = $tensors[0]->shape();
        $resultOffset = $tensors[0]->offset();
        $resultShape[$dim] = array_reduce($tensors, function ($carry, $tensor) use ($dim) {
            return $carry + $tensor->shape()[$dim];
        }, 0);

        // Create a new array to store the accumulated values
        $resultSize = array_product($resultShape);

        $result = new \SplFixedArray($resultSize);

        // Create output tensor of same type as first
        $resultType = $tensors[0]->dtype();

        if ($dim === 0) {
            // Handle special case for performance reasons

            $offset = 0;
            foreach ($tensors as $t) {
                for ($i = 0; $i < $t->_buffer->count(); $i++) {
                    $result[$offset++] = $t->buffer()[$i];
                }
            }
        } else {
            $currentDim = 0;

            foreach ($tensors as $tensor) {
                for ($i = 0; $i < $tensor->_buffer->count(); $i++) {
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

}