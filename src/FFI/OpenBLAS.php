<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

/**
 * OpenBLAS - Wrapper for the OpenBLAS library
 * 
 * This class provides access to the OpenBLAS library headers and shared libraries
 * without actually loading them into memory, as they will be loaded by the
 * OpenBLASFactory from rindow/math-matrix.
 */
class OpenBLAS extends NativeLibrary
{
    public function __construct()
    {
        parent::__construct(false);
    }

    protected function getHeaderName(): string
    {
        return 'openblas';
    }

    protected function getLibraryName(): string
    {
        return 'libopenblas';
    }
}
