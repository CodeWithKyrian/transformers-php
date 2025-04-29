<?php

namespace Codewithkyrian\Transformers\FFI;

use Jcupitt\Vips\FFI;

class Libvips extends NativeLibrary 
{
    public function __construct()
    {
        parent::__construct(false);

        FFI::addLibraryPath($this->getLibDirectory());
    }

    public static function setup()
    {
        $libvips = new Libvips();
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
