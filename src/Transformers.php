<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers;

use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use OnnxRuntime\FFI;
use OnnxRuntime\Vendor;
use function Codewithkyrian\Transformers\Utils\joinPaths;

class Transformers
{
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
//        FFI::$lib = self::libFile();

        Image::$imagine = match (self::$imageDriver) {
            ImageDriver::IMAGICK => new \Imagine\Imagick\Imagine(),
            ImageDriver::GD => new \Imagine\GD\Imagine(),
            ImageDriver::VIPS => new \Imagine\Vips\Imagine(),
        };
    }

    public static function libFile(): string
    {
        $template = joinPaths(Transformers::$cacheDir, self::platform('file'), 'lib', self::platform('lib'));

        return str_replace('{{version}}', Vendor::VERSION, $template);
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

    public static function platform($key): string
    {
        $platformKey = match (PHP_OS_FAMILY) {
            'Windows' => 'x64-windows',
            'Darwin' => php_uname('m') == 'x86_64' ? 'x86_64-darwin' : 'arm64-darwin',
            default => php_uname('m') == 'x86_64' ? 'x86_64-linux' : 'aarch64-linux',
        };
        return Vendor::PLATFORMS[$platformKey][$key];
    }
}