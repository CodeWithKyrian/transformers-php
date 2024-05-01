<?php

namespace Codewithkyrian\Transformers\Utils;

use Interop\Polite\Math\Matrix\LinearBuffer;
use Interop\Polite\Math\Matrix\NDArray;
use TypeError;
use InvalidArgumentException;
use OutOfRangeException;
use LogicException;
use FFI;

class complex_t
{
    public float $real;
    public float $imag;
}

class TensorBuffer implements LinearBuffer
{
    const MAX_BYTES = 2147483648; // 2**31
    static protected ?FFI $ffi = null;

    /** @var array<int,string> $typeString */
    protected static $typeString = [
        NDArray::bool => 'uint8_t',
        NDArray::int8 => 'int8_t',
        NDArray::int16 => 'int16_t',
        NDArray::int32 => 'int32_t',
        NDArray::int64 => 'int64_t',
        NDArray::uint8 => 'uint8_t',
        NDArray::uint16 => 'uint16_t',
        NDArray::uint32 => 'uint32_t',
        NDArray::uint64 => 'uint64_t',
        //NDArray::float8  => 'N/A',
        //NDArray::float16 => 'N/A',
        NDArray::float32 => 'float',
        NDArray::float64 => 'double',
        //NDArray::complex16 => 'N/A',
        //NDArray::complex32 => 'N/A',
        NDArray::complex64 => 'rindow_complex_float',
        NDArray::complex128 => 'rindow_complex_double',
    ];
    /** @var array<int,int> $valueSize */
    protected static array $valueSize = [
        NDArray::bool => 1,
        NDArray::int8 => 1,
        NDArray::int16 => 2,
        NDArray::int32 => 4,
        NDArray::int64 => 8,
        NDArray::uint8 => 1,
        NDArray::uint16 => 2,
        NDArray::uint32 => 4,
        NDArray::uint64 => 8,
        //NDArray::float8  => 'N/A',
        //NDArray::float16 => 'N/A',
        NDArray::float32 => 4,
        NDArray::float64 => 8,
        //NDArray::complex16 => 'N/A',
        //NDArray::complex32 => 'N/A',
        NDArray::complex64 => 8,
        NDArray::complex128 => 16,
    ];

    protected int $size;
    protected int $dtype;
    protected object $data;

    public function __construct(int $size, int $dtype)
    {
        if (self::$ffi === null) {
            $code = file_get_contents(__DIR__ . '/../../libs/buffer.h');
            self::$ffi = FFI::cdef($code);
        }
        if (!isset(self::$typeString[$dtype])) {
            throw new InvalidArgumentException("Invalid data type");
        }
        $limitsize = intdiv(self::MAX_BYTES, self::$valueSize[$dtype]);
        if ($size >= $limitsize) {
            throw new InvalidArgumentException("Data size is too large.");
        }
        $this->size = $size;
        $this->dtype = $dtype;
        $declaration = self::$typeString[$dtype];

        if ($size === 0) {
            $this->data = self::$ffi->new("void *");
        } else {
            $this->data = self::$ffi->new("{$declaration}[{$size}]");
        }

    }

    protected function assertOffset(string $method, mixed $offset): void
    {
        if (!is_int($offset)) {
            throw new TypeError($method . '(): Argument #1 ($offset) must be of type int');
        }
        if ($offset < 0 || $offset >= $this->size) {
            throw new OutOfRangeException($method . '(): Index invalid or out of range');
        }
    }

    protected function assertOffsetIsInt(string $method, mixed $offset): void
    {
        if (!is_int($offset)) {
            throw new TypeError($method . '(): Argument #1 ($offset) must be of type int');
        }
    }

    protected function isComplex(int $dtype = null): bool
    {
        $dtype = $dtype ?? $this->dtype;
        return $dtype == NDArray::complex64 || $dtype == NDArray::complex128;
    }

    public function dtype(): int
    {
        return $this->dtype;
    }

    public function valueSize(): int
    {
        return self::$valueSize[$this->dtype];
    }

    public function addr(int $offset): FFI\CData
    {
        return FFI::addr($this->data[$offset]);
    }

    public function count(): int
    {
        return $this->size;
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->assertOffsetIsInt('offsetExists', $offset);

        return ($offset >= 0) && ($offset < $this->size);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->assertOffset('offsetGet', $offset);

        $value = $this->data[$offset];

        if ($this->dtype === NDArray::bool) {
            $value = (bool)$value;
        }

        return $value;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assertOffset('offsetSet', $offset);

        if ($this->isComplex()) {
            if (is_array($value)) {
                [$real, $imag] = $value;
            } elseif (is_object($value)) {
                $real = $value->real;
                $imag = $value->imag;
            } else {
                $type = gettype($value);
                throw new InvalidArgumentException("Cannot convert to complex number.: " . $type);
            }

            /** @var complex_t $value */
            $value = self::$ffi->new(self::$typeString[$this->dtype]);
            $value->real = $real;
            $value->imag = $imag;
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException("Illegal Operation");
    }

    public function dump(): string
    {
        $byte = self::$valueSize[$this->dtype] * $this->size;
        if ($byte === 0) return '';
        $buf = FFI::new("char[$byte]");
        FFI::memcpy($buf, $this->data, $byte);
        return FFI::string($buf, $byte);
    }

    public function load(string $string): void
    {
        $byte = self::$valueSize[$this->dtype] * $this->size;
        $strlen = strlen($string);
        if ($strlen != $byte) {
            throw new InvalidArgumentException("Unmatched data size. buffer size is $byte. $strlen byte given.");
        }
        FFI::memcpy($this->data, $string, $strlen);
    }
}
