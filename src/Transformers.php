<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers;

use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use OnnxRuntime\Vendor;
use function Codewithkyrian\Transformers\Utils\joinPaths;

class Transformers
{
    public const VERSION = '0.4.0';
    public const  LIBS_DIR = __DIR__ . '/../libs/';

    protected const LIBRARIES = [
        'x86_64-darwin' => [
            'archive' => 'libraries-osx-x86_64-{{version}}.tar.gz',
            'checksum' => 'f72a2bcca40e2650756c6b96c69ef031236aaab1b98673e744da4eef0c4bddbd',
            'rindowmatlib.serial' => 'rindow-matlib-Darwin-1.0.0/lib/librindowmatlib_serial.dylib',
            'rindowmatlib.openmp' => 'rindow-matlib-Darwin-1.0.0/lib/librindowmatlib_openmp.dylib',
            'openblas.serial' => 'openblas-osx-x86_64-0.3.27/lib/libopenblas_serial.dylib',
            'openblas.openmp' => 'openblas-osx-x86_64-0.3.27/lib/libopenblas_openmp.dylib',
            'openblas.include' => 'openblas-osx-x86_64-0.3.27/include/openblas.h',
            'lapacke.serial' => 'openblas-osx-x86_64-0.3.27/lib/libopenblas_serial.dylib',
            'lapacke.openmp' => 'openblas-osx-x86_64-0.3.27/lib/libopenblas_openmp.dylib'
        ],
        'arm64-darwin' => [
            'archive' => 'libraries-osx-arm64-{{version}}.tar.gz',
            'checksum' => 'f72a2bcca40e2650756c6b96c69ef031236aaab1b98673e744da4eef0c4bddbd',
            'rindowmatlib.serial' => 'rindow-matlib-Darwin-1.0.0/lib/librindowmatlib_serial.dylib',
            'rindowmatlib.openmp' => 'rindow-matlib-Darwin-1.0.0/lib/librindowmatlib_openmp.dylib',
            'openblas.serial' => 'openblas-osx-arm64-0.3.27/lib/libopenblas_serial.dylib',
            'openblas.openmp' => 'openblas-osx-arm64-0.3.27/lib/libopenblas_openmp.dylib',
            'openblas.include' => 'openblas-osx-arm64-0.3.27/include/openblas.h',
            'lapacke.serial' => 'openblas-osx-arm64-0.3.27/lib/libopenblas_serial.dylib',
            'lapacke.openmp' => 'openblas-osx-arm64-0.3.27/lib/libopenblas_openmp.dylib'
        ],
        'x86_64-linux' => [

        ],
        'aarch64-linux' => [

        ],
        'x64-windows' => [

        ]
    ];


    public static string $cacheDir = '.transformers-cache';

    public static string $remoteHost = 'https://huggingface.co';

    public static string $remotePathTemplate = '{model}/resolve/{revision}/{file}';

    public static ?string $authToken = null;

    public static ?string $userAgent = 'transformers-php/0.1.0';

    public static ImageDriver $imageDriver = ImageDriver::IMAGICK;

    public static function setup(): static
    {
        return new static;
    }

    public function apply(): void
    {
        Image::setDriver(self::$imageDriver);
    }

    /**
     * Set the default cache directory for transformers models and tokenizers
     * @param string $cacheDir
     * @return $this
     */
    public function setCacheDir(?string $cacheDir): static
    {
        if ($cacheDir != null) self::$cacheDir = $cacheDir;

        return $this;
    }

    /**
     * Set the remote host for downloading models and tokenizers. This is useful for using a custom mirror
     * or a local server for downloading models and tokenizers
     * @param string $remoteHost
     * @return $this
     */
    public function setRemoteHost(string $remoteHost): static
    {
        self::$remoteHost = $remoteHost;

        return $this;
    }

    /**
     * Set the remote path template for downloading models and tokenizers. This is useful for using a custom mirror
     * or a local server for downloading models and tokenizers
     * @param string $remotePathTemplate
     * @return $this
     */
    public function setRemotePathTemplate(string $remotePathTemplate): static
    {
        self::$remotePathTemplate = $remotePathTemplate;

        return $this;
    }

    /**
     * Set the authentication token for downloading models and tokenizers. This is useful for using a private model
     * repository in Hugging Face
     * @param string $authToken
     * @return $this
     */
    public function setAuthToken(string $authToken): static
    {
        self::$authToken = $authToken;

        return $this;
    }

    /**
     * Set the user agent for downloading models and tokenizers. This is useful for using a custom user agent
     * for downloading models and tokenizers
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent(string $userAgent): static
    {
        self::$userAgent = $userAgent;

        return $this;
    }

    public function setImageDriver(ImageDriver $imageDriver): static
    {
        self::$imageDriver = $imageDriver;

        return $this;
    }

    public static function getLib(string $key): string
    {
        $platformKey = match (PHP_OS_FAMILY) {
            'Windows' => 'x64-windows',
            'Darwin' => php_uname('m') == 'x86_64' ? 'x86_64-darwin' : 'arm64-darwin',
            default => php_uname('m') == 'x86_64' ? 'x86_64-linux' : 'aarch64-linux',
        };

        $filename = self::LIBRARIES[$platformKey][$key];

        return joinPaths(Transformers::LIBS_DIR, $filename);
    }
}