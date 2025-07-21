<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tensor;

use Codewithkyrian\Transformers\FFI\OpenBLAS;
use Codewithkyrian\Transformers\FFI\RindowMatlib;
use Rindow\Math\Matrix\Drivers\AbstractMatlibService;
use Rindow\Matlib\FFI\MatlibFactory;
use function Codewithkyrian\Transformers\Utils\basePath;

class TensorService extends AbstractMatlibService
{
    protected function injectDefaultFactories(): void
    {
        $this->bufferFactory = new TensorBufferFactory();

        $openBlas = new OpenBLAS();
        $this->openblasFactory = new OpenBLASFactory(
            headerFile: $openBlas->getHeaderPath(),
            libFiles: [$openBlas->getLibraryPath()],
        );

        $rindowMatlib = new RindowMatlib();
        $this->mathFactory = new MatlibFactory(
            libFiles: [$rindowMatlib->getLibraryPath()]
        );
    }
}