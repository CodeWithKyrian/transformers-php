<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

/**
 * RindowMatlib - Wrapper for the Rindow Matlib library
 * 
 * This class provides access to the Rindow Matlib library headers and shared libraries
 * without actually loading them into memory, as they will be loaded by the
 * MatlibFactory from rindow/math-matrix.
 */
class RindowMatlib extends NativeLibrary
{
    public function __construct()
    {
        parent::__construct(false);
    }

    protected function getHeaderName(): string
    {
        return 'rindowmatlib';
    }

    protected function getLibraryName(): string
    {
        return 'rindowmatlib';
    }

    protected function getLibraryVersion(): string
    {
        return '1.1.1';
    }
}
