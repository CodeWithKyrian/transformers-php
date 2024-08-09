<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tensor;

use Codewithkyrian\Transformers\FFI\Lib;
use Rindow\Math\Matrix\Drivers\AbstractMatlibService;
use Rindow\Matlib\FFI\MatlibFactory;

class TensorService extends AbstractMatlibService
{
    protected function injectDefaultFactories(): void
    {
        $this->bufferFactory = new TensorBufferFactory();

        $this->openblasFactory = new OpenBLASFactory(
            headerFile: Lib::OpenBlas->header(),
            libFiles: [Lib::OpenBlas->library()],
        );

        $this->mathFactory = new MatlibFactory(
            libFiles: [Lib::RindowMatlib->library()]
        );
    }
}