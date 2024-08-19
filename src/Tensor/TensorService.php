<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tensor;

use Codewithkyrian\TransformersLibsLoader\Library;
use Rindow\Math\Matrix\Drivers\AbstractMatlibService;
use Rindow\Matlib\FFI\MatlibFactory;
use function Codewithkyrian\Transformers\Utils\basePath;

class TensorService extends AbstractMatlibService
{
    protected function injectDefaultFactories(): void
    {
        $this->bufferFactory = new TensorBufferFactory();

        $this->openblasFactory = new OpenBLASFactory(
            headerFile: Library::OpenBlas->header(basePath('includes')),
            libFiles: [Library::OpenBlas->library(basePath('libs'))],
        );

        $this->mathFactory = new MatlibFactory(
            libFiles: [Library::RindowMatlib->library(basePath('libs'))]
        );
    }
}