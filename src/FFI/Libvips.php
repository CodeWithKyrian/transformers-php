<?php

namespace Codewithkyrian\Transformers\FFI;

class Libvips extends NativeLibrary 
{
    public function __construct()
    {
        parent::__construct(false);
    }

    protected function getHeaderName(): string
    {
        return 'vips';
    }
    
    protected function getLibraryName(): string
    {
        return 'libvips';
    }
}
