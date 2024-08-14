<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers;

use Codewithkyrian\Transformers\Utils\ImageDriver;
use RuntimeException;

class Transformers
{
    protected static bool $isInitialized = false;

    protected static string $cacheDir = '.transformers-cache';

    protected static string $remoteHost = 'https://huggingface.co';

    protected static string $remotePathTemplate = '{model}/resolve/{revision}/{file}';

    protected static ?string $authToken = null;

    protected static ?string $userAgent = 'transformers-php/0.4.0';

    protected static ImageDriver $imageDriver;


    /**
     * Initializes the static class by creating a new instance, setting the `$isInitialized` flag to `true`,
     * creating a resolver using the factory method, and adding the `$libsDir` directory to the resolver.
     *
     * @return static The newly created instance of the static class.
     */
    public static function setup(): static
    {
        $instance = new static;

        self::$isInitialized = true;

        return $instance;
    }

    /**
     * Resets the static class by setting the `$isInitialized` flag to `false` and removing the `$libsDir` directory
     * from the resolver.
     *
     * @return void
     */
    public static function tearDown(): void
    {
        self::$isInitialized = false;
    }

    public static function apply() {}

    /**
     * Ensures that the static class has been initialized. If not, an exception is thrown.
     */
    public static function ensureInitialized(): void
    {
        if (!self::$isInitialized) {
            throw new RuntimeException('Transformers has not been initialized. Please call `Transformers::setup()` first.');
        }
    }

    /**
     * Set the default cache directory for transformers models and tokenizers
     *
     * @param string|null $cacheDir
     *
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
     *
     * @param string $remoteHost
     *
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
     *
     * @param string $remotePathTemplate
     *
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
     *
     * @param string $authToken
     *
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
     *
     * @param string $userAgent
     *
     * @return $this
     */
    public function setUserAgent(string $userAgent): static
    {
        self::$userAgent = $userAgent;

        return $this;
    }

    /**
     * Set the image driver for processing images.
     *
     * @param ImageDriver $imageDriver
     *
     * @return $this
     */
    public function setImageDriver(ImageDriver $imageDriver): static
    {
        self::$imageDriver = $imageDriver;

        return $this;
    }

    public static function getImageDriver(): ?ImageDriver
    {
        self::ensureInitialized();

        if (!isset(self::$imageDriver)) {
            throw new RuntimeException('Image driver not set. Please set the image driver using `Transformers::setup()->setImageDriver()`');
        }

        return self::$imageDriver;
    }

    public static function getRemoteHost(): string
    {
        self::ensureInitialized();

        return self::$remoteHost;
    }

    public static function getRemotePathTemplate(): string
    {
        self::ensureInitialized();

        return self::$remotePathTemplate;
    }

    public static function getAuthToken(): ?string
    {
        self::ensureInitialized();

        return self::$authToken;
    }

    public static function getUserAgent(): string
    {
        self::ensureInitialized();

        return self::$userAgent;
    }

    public static function getCacheDir(): string
    {
        self::ensureInitialized();

        return self::$cacheDir;
    }
}