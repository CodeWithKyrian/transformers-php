<?php

namespace Codewithkyrian\Transformers\Utils;

use FFI;

class TensorBufferFactory
{
    public function isAvailable() : bool
    {
        return class_exists(FFI::class);
    }

    public function Buffer(int $size, int $dtype) : TensorBuffer
    {
        return new TensorBuffer($size, $dtype);
    }
}