<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\Transformers;
use Rindow\Math\Matrix\Drivers\AbstractMatlibService;
use Rindow\Matlib\FFI\MatlibFactory;
use Rindow\OpenBLAS\FFI\OpenBLASFactory;

class TensorService extends AbstractMatlibService
{

    public function __construct(
        object $bufferFactory = null,
        object $mathFactory = null,
        object $openblasFactory = null,
        object $openclFactory = null,
        object $clblastFactory = null,
        object $blasCLFactory = null,
        object $mathCLFactory = null,
        object $bufferCLFactory = null,
    )
    {
        $openblasFactory = new OpenBLASFactory(
            headerFile: Transformers::getLib('openblas.include'),
            libFiles: [Transformers::getLib('openblas.openmp')],
            lapackeLibs: [Transformers::getLib('lapacke.openmp')],
        );

        $mathFactory = new MatlibFactory(
            libFiles: [Transformers::getLib('rindowmatlib.openmp')]
        );

        $bufferFactory = new TensorBufferFactory();

        if (!$openblasFactory->isAvailable()
            || !$mathFactory->isAvailable()
        ) {
            $openblasFactory = new OpenBLASFactory(
                headerFile: Transformers::getLib('openblas.include'),
                libFiles: [Transformers::getLib('openblas.serial')],
                lapackeLibs: [Transformers::getLib('lapacke.serial')],
            );

            $mathFactory = new MatlibFactory(
                libFiles: [Transformers::getLib('rindowmatlib.serial')]
            );
        }

        parent::__construct(
            bufferFactory: $bufferFactory,
            openblasFactory: $openblasFactory,
            mathFactory: $mathFactory,
            openclFactory: $openclFactory,
            clblastFactory: $clblastFactory,
            blasCLFactory: $blasCLFactory,
            mathCLFactory: $mathCLFactory,
            bufferCLFactory: $bufferCLFactory,
        );
    }


}