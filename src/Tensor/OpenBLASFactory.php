<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tensor;

use FFI;
use FFI\Exception as FFIException;
use Rindow\Math\Matrix\Drivers\MatlibPHP\PhpLapack;
use Rindow\OpenBLAS\FFI\Blas;
use RuntimeException;

/**
 */
class OpenBLASFactory
{
    private static ?FFI $ffi = null;


    /**
     * @param array<string> $libFiles
     * @param array<string> $lapackeLibs
     */
    public function __construct(
        string $headerFile,
        array  $libFiles,
    )
    {
        if (self::$ffi !== null) {
            return;
        }
        if (!extension_loaded('ffi')) {
            return;
        }

        $code = file_get_contents($headerFile);

        foreach ($libFiles as $filename) {
            try {
                $ffi = FFI::cdef($code, $filename);
            } catch (FFIException $e) {
                continue;
            }

            self::$ffi = $ffi;
            break;
        }
    }

    public function isAvailable(): bool
    {
        return self::$ffi !== null;
    }

    public function Blas(): Blas
    {
        if (self::$ffi == null) {
            throw new RuntimeException('openblas library not loaded.');
        }
        return new Blas(self::$ffi);
    }

    public function Lapack(): PhpLapack
    {
        return new PhpLapack();
    }
}