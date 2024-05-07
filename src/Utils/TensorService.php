<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\Libraries;
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
        [$openblasFactory, $mathFactory, $bufferFactory] = $this->initializeFactories();

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

    private function initializeFactories(): array
    {
        $bufferFactory = new TensorBufferFactory();

        // Try initializing OpenMP-compatible factories
        $openblasFactory = new OpenBLASFactory(
            headerFile: Libraries::OpenBlas_OpenMP->headerFile(),
            libFiles: [Libraries::OpenBlas_OpenMP->libFile()],
            lapackeLibs: [Libraries::Lapacke_OpenMP->libFile()],
        );

        $mathFactory = new MatlibFactory(
            libFiles: [Libraries::RindowMatlib_OpenMP->libFile()]
        );

        // Check if OpenMP-compatible factories are available
        if ($openblasFactory->isAvailable() && $mathFactory->isAvailable()) {
            return [$openblasFactory, $mathFactory, $bufferFactory];
        }

        // If OpenMP is not available, try initializing serial-compatible factories
        $openblasFactory = new OpenBLASFactory(
            headerFile: Libraries::OpenBlas_Serial->headerFile(),
            libFiles: [Libraries::OpenBlas_Serial->libFile()],
            lapackeLibs: [Libraries::Lapacke_Serial->libFile()],
        );

        $mathFactory = new MatlibFactory(
            libFiles: [Libraries::RindowMatlib_Serial->libFile()]
        );

        // Check if serial-compatible factories are available
        if ($openblasFactory->isAvailable() && $mathFactory->isAvailable()) {
            return [$openblasFactory, $mathFactory, $bufferFactory];
        }

        // If neither OpenMP nor serial is available, return null values,
        return [null, null, null];
    }


}