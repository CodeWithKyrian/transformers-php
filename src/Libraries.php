<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers;

use function Codewithkyrian\Transformers\Utils\joinPaths;

enum Libraries
{
    case OpenBlas_Serial;
    case OpenBlas_OpenMP;
    case RindowMatlib_Serial;
    case RindowMatlib_OpenMP;
    case Lapacke_Serial;
    case Lapacke_OpenMP;
    case OnnxRuntime;

    public const LIBS_DIR = __DIR__ . '/../libs/';
    public const VERSIONS_FILE = __DIR__ . '/../libs/VERSIONS';

    protected const LIBRARIES = [
        'x86_64-darwin' => [
            'archive' => [
                'name' => 'libraries-osx-x86_64-{{version}}',
                'format' => 'tar.gz',
                'checksum' => 'f72a2bcca40e2650756c6b96c69ef031236aaab1b98673e744da4eef0c4bddbd',
            ],
            'rindowmatlib.serial' => [
                'folder' => 'rindow-matlib-Darwin-{{version}}',
                'lib' => 'librindowmatlib_serial.dylib',
                'header' => 'rindow-matlib.h'
            ],
            'rindowmatlib.openmp' => [
                'folder' => 'rindow-matlib-Darwin-{{version}}',
                'lib' => 'librindowmatlib_openmp.dylib',
                'header' => 'rindow-matlib.h'
            ],
            'openblas.serial' => [
                'folder' => 'openblas-osx-x86_64-{{version}}',
                'lib' => 'libopenblas_serial.dylib',
                'header' => 'openblas.h'
            ],
            'openblas.openmp' => [
                'folder' => 'openblas-osx-x86_64-{{version}}',
                'lib' => 'libopenblas_openmp.dylib',
                'header' => 'openblas.h'
            ],
            'lapacke.serial' => [
                'folder' => 'openblas-osx-x86_64-{{version}}',
                'lib' => 'libopenblas_serial.dylib',
                'header' => 'lapacke.h'
            ],
            'lapacke.openmp' => [
                'folder' => 'openblas-osx-x86_64-{{version}}',
                'lib' => 'libopenblas_openmp.dylib',
                'header' => 'lapacke.h'
            ],
            'onnxruntime' => [
                'folder' => 'onnxruntime-osx-x86_64-{{version}}',
                'lib' => 'libonnxruntime.dylib',
                'header' => 'onnxruntime.h'
            ],
        ],

        'arm64-darwin' => [
            'archive' => [
                'name' => 'libraries-osx-arm64-{{version}}',
                'format' => 'tar.gz',
                'checksum' => 'f72a2bcca40e2650756c6b96c69ef031236aaab1b98673e744da4eef0c4bddbd',
            ],
            'rindowmatlib.serial' => [
                'folder' => 'rindow-matlib-Darwin-{{version}}',
                'lib' => 'librindowmatlib_serial.dylib',
                'header' => 'rindow-matlib.h'
            ],
            'rindowmatlib.openmp' => [
                'folder' => 'rindow-matlib-Darwin-{{version}}',
                'lib' => 'librindowmatlib_openmp.dylib',
                'header' => 'rindow-matlib.h'
            ],
            'openblas.serial' => [
                'folder' => 'openblas-osx-arm64-{{version}}',
                'lib' => 'libopenblas_serial.dylib',
                'header' => 'openblas.h'
            ],
            'openblas.openmp' => [
                'folder' => 'openblas-osx-arm64-{{version}}',
                'lib' => 'libopenblas_openmp.dylib',
                'header' => 'openblas.h'
            ],
            'lapacke.serial' => [
                'folder' => 'openblas-osx-arm64-{{version}}',
                'lib' => 'libopenblas_serial.dylib',
                'header' => 'lapacke.h'
            ],
            'lapacke.openmp' => [
                'folder' => 'openblas-osx-arm64-{{version}}',
                'lib' => 'libopenblas_openmp.dylib',
                'header' => 'lapacke.h'
            ],
            'onnxruntime' => [
                'folder' => 'onnxruntime-osx-arm64-{{version}}',
                'lib' => 'libonnxruntime.dylib',
                'header' => 'onnxruntime.h'
            ],
        ],

        'x86_64-linux' => [
            'archive' => [
                'name' => 'libraries-linux-x86_64-{{version}}',
                'format' => 'tar.gz',
                'checksum' => 'f72a2bcca40e2650756c6b96c69ef031236aaab1b98673e744da4eef0c4bddbd',
            ],
            'rindowmatlib.serial' => [
                'folder' => 'rindow-matlib-Linux-{{version}}',
                'lib' => 'librindowmatlib_serial.so',
                'header' => 'rindow-matlib.h'
            ],
            'rindowmatlib.openmp' => [
                'folder' => 'rindow-matlib-Linux-{{version}}',
                'lib' => 'librindowmatlib_openmp.so',
                'header' => 'rindow-matlib.h'
            ],
            'openblas.serial' => [
                'folder' => 'openblas-linux-x86_64-{{version}}',
                'lib' => 'libopenblas_serial.so',
                'header' => 'openblas.h'
            ],
            'openblas.openmp' => [
                'folder' => 'openblas-linux-x86_64-{{version}}',
                'lib' => 'libopenblas_openmp.so',
                'header' => 'openblas.h'
            ],
            'lapacke.serial' => [
                'folder' => 'openblas-linux-x86_64-{{version}}',
                'lib' => 'liblapacke_serial.so',
                'header' => 'lapacke.h'
            ],
            'lapacke.openmp' => [
                'folder' => 'openblas-linux-x86_64-{{version}}',
                'lib' => 'liblapacke_openmp.so',
                'header' => 'lapacke.h'
            ],
            'onnxruntime' => [
                'folder' => 'onnxruntime-linux-x86_64-{{version}}',
                'lib' => 'libonnxruntime.so',
                'header' => 'onnxruntime.h'
            ],
        ],

        'aarch64-linux' => [
            'archive' => [
                'name' => 'libraries-linux-arm64-{{version}}',
                'format' => 'tar.gz',
                'checksum' => 'f72a2bcca40e2650756c6b96c69ef031236aaab1b98673e744da4eef0c4bddbd',
            ],
            'rindowmatlib.serial' => [
                'folder' => 'rindow-matlib-Linux-{{version}}',
                'lib' => 'librindowmatlib_serial.so',
                'header' => 'rindow-matlib.h'
            ],
            'rindowmatlib.openmp' => [
                'folder' => 'rindow-matlib-Linux-{{version}}',
                'lib' => 'librindowmatlib_openmp.so',
                'header' => 'rindow-matlib.h'
            ],
            'openblas.serial' => [
                'folder' => 'openblas-linux-arm64-{{version}}',
                'lib' => 'libopenblas_serial.so',
                'header' => 'openblas.h'
            ],
            'openblas.openmp' => [
                'folder' => 'openblas-linux-arm64-{{version}}',
                'lib' => 'libopenblas_openmp.so',
                'header' => 'openblas.h'
            ],
            'lapacke.serial' => [
                'folder' => 'openblas-linux-arm64-{{version}}',
                'lib' => 'liblapacke_serial.so',
                'header' => 'lapacke.h'
            ],
            'lapacke.openmp' => [
                'folder' => 'openblas-linux-arm64-{{version}}',
                'lib' => 'liblapacke_openmp.so',
                'header' => 'lapacke.h'
            ],
            'onnxruntime' => [
                'folder' => 'onnxruntime-linux-arm64-{{version}}',
                'lib' => 'libonnxruntime.so',
                'header' => 'onnxruntime.h'
            ],
        ],

        'x64-windows' => [
            'archive' => [
                'name' => 'libraries-windows-x64-{{version}}',
                'format' => 'zip',
                'checksum' => 'f72a2bcca40e2650756c6b96c69ef031236aaab1b98673e744da4eef0c4bddbd',
            ],
            'rindowmatlib.serial' => [
                'folder' => 'rindow-matlib-Windows-{{version}}',
                'lib' => 'rindowmatlib_serial.dll',
                'header' => 'rindow-matlib.h'
            ],
            'rindowmatlib.openmp' => [
                'folder' => 'rindow-matlib-Windows-{{version}}',
                'lib' => 'rindowmatlib_openmp.dll',
                'header' => 'rindow-matlib.h'
            ],
            'openblas.serial' => [
                'folder' => 'openblas-windows-x64-{{version}}',
                'lib' => 'openblas_serial.dll',
                'header' => 'openblas.h'
            ],
            'openblas.openmp' => [
                'folder' => 'openblas-windows-x64-{{version}}',
                'lib' => 'openblas_openmp.dll',
                'header' => 'openblas.h'
            ],
            'lapacke.serial' => [
                'folder' => 'openblas-windows-x64-{{version}}',
                'lib' => 'lapacke_serial.dll',
                'header' => 'lapacke.h'
            ],
            'lapacke.openmp' => [
                'folder' => 'openblas-windows-x64-{{version}}',
                'lib' => 'lapacke_openmp.dll',
                'header' => 'lapacke.h'
            ],
            'onnxruntime' => [
                'folder' => 'onnxruntime-windows-x64-{{version}}',
                'lib' => 'onnxruntime.dll',
                'header' => 'onnxruntime.h'
            ],
        ],
    ];

    public static function platformKey(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'x64-windows',
            'Darwin' => php_uname('m') == 'x86_64' ? 'x86_64-darwin' : 'arm64-darwin',
            default => php_uname('m') == 'x86_64' ? 'x86_64-linux' : 'aarch64-linux',
        };
    }

    public function version(): string
    {
        $versions = parse_ini_file(self::VERSIONS_FILE);

        return match ($this) {
            self::OpenBlas_Serial,
            self::OpenBlas_OpenMP,
            self::Lapacke_Serial,
            self::Lapacke_OpenMP => $versions['OPENBLAS'],
            self::RindowMatlib_Serial,
            self::RindowMatlib_OpenMP => $versions['RINDOW_MATLIB'],
            self::OnnxRuntime => $versions['ONNXRUNTIME'],
        };
    }

    public function folder(): string
    {
        return match ($this) {
            self::OpenBlas_Serial => self::LIBRARIES[self::platformKey()]['openblas.serial']['folder'],
            self::OpenBlas_OpenMP => self::LIBRARIES[self::platformKey()]['openblas.openmp']['folder'],
            self::RindowMatlib_Serial => self::LIBRARIES[self::platformKey()]['rindowmatlib.serial']['folder'],
            self::RindowMatlib_OpenMP => self::LIBRARIES[self::platformKey()]['rindowmatlib.openmp']['folder'],
            self::Lapacke_Serial => self::LIBRARIES[self::platformKey()]['lapacke.serial']['folder'],
            self::Lapacke_OpenMP => self::LIBRARIES[self::platformKey()]['lapacke.openmp']['folder'],
            self::OnnxRuntime => self::LIBRARIES[self::platformKey()]['onnxruntime']['folder'],
        };
    }


    public function libFile(): string
    {
        $file = match ($this) {
            self::OpenBlas_Serial => self::LIBRARIES[self::platformKey()]['openblas.serial']['lib'],
            self::OpenBlas_OpenMP => self::LIBRARIES[self::platformKey()]['openblas.openmp']['lib'],
            self::RindowMatlib_Serial => self::LIBRARIES[self::platformKey()]['rindowmatlib.serial']['lib'],
            self::RindowMatlib_OpenMP => self::LIBRARIES[self::platformKey()]['rindowmatlib.openmp']['lib'],
            self::Lapacke_Serial => self::LIBRARIES[self::platformKey()]['lapacke.serial']['lib'],
            self::Lapacke_OpenMP => self::LIBRARIES[self::platformKey()]['lapacke.openmp']['lib'],
            self::OnnxRuntime => self::LIBRARIES[self::platformKey()]['onnxruntime']['lib'],
        };

        $folder = str_replace('{{version}}', $this->version(), $this->folder());

        return joinPaths(self::LIBS_DIR, $folder, 'lib', $file);
    }

    public function headerFile(): string
    {
        $file = match ($this) {
            self::OpenBlas_Serial => self::LIBRARIES[self::platformKey()]['openblas.serial']['header'],
            self::OpenBlas_OpenMP => self::LIBRARIES[self::platformKey()]['openblas.openmp']['header'],
            self::RindowMatlib_Serial => self::LIBRARIES[self::platformKey()]['rindowmatlib.serial']['header'],
            self::RindowMatlib_OpenMP => self::LIBRARIES[self::platformKey()]['rindowmatlib.openmp']['header'],
            self::Lapacke_Serial => self::LIBRARIES[self::platformKey()]['lapacke.serial']['header'],
            self::Lapacke_OpenMP => self::LIBRARIES[self::platformKey()]['lapacke.openmp']['header'],
            self::OnnxRuntime => self::LIBRARIES[self::platformKey()]['onnxruntime']['header'],
        };

        $folder = str_replace('{{version}}', $this->version(), $this->folder());

        return joinPaths(self::LIBS_DIR, $folder, 'include', $file);
    }


}
