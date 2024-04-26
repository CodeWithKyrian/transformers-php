<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Rindow\Math\Matrix\Drivers\AbstractMatlibService;
use Rindow\Matlib\FFI\MatlibFactory;
use Rindow\OpenBLAS\FFI\OpenBLASFactory;

class TensorService extends AbstractMatlibService
{
    protected string $openBlasHeader = __DIR__ . '/../../libs/openblas.h';
    protected string $openBlasLib = __DIR__ . '/../../libs/libopenblas.dylib';
    protected string $rindowMatlibLib = __DIR__ . '/../../libs/librindowmatlib.dylib';

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
            headerFile: $this->openBlasHeader,
            libFiles: [$this->openBlasLib],
            lapackeLibs: [$this->openBlasLib],
        );

        $mathFactory = new MatlibFactory(
            libFiles: [$this->rindowMatlibLib]
        );

        $bufferFactory = new TensorBufferFactory();

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