<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers;

use Codewithkyrian\Transformers\Utils\ImageDriver;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Transformers
{
    protected static string $cacheDir = '.transformers-cache';

    protected static string $remoteHost = 'https://huggingface.co';

    protected static string $remotePathTemplate = '{model}/resolve/{revision}/{file}';

    protected static ?string $authToken = null;

    protected static ?string $userAgent = 'transformers-php/0.4.0';

    protected static ImageDriver $imageDriver;

    protected static ?LoggerInterface $logger = null;

    /**
     * Returns a new instance of the static class.
     *
     * @return static The newly created instance of the static class.
     */
    public static function setup(): static
    {
        return new static;
    }

    public static function apply() {}

    /**
     * Set the default cache directory for transformers models and tokenizers
     *
     * @param string $cacheDir
     *
     * @return $this
     */
    public function setCacheDir(string $cacheDir): static
    {
        self::$cacheDir = $cacheDir;

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

    /**
     * Set the logger for debugging.
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger) : static
    {
        self::$logger = $logger;

        return $this;
    }

    public static function getCacheDir(): string
    {
        return self::$cacheDir;
    }

    public static function getRemoteHost(): string
    {
        return self::$remoteHost;
    }

    public static function getRemotePathTemplate(): string
    {
        return self::$remotePathTemplate;
    }

    public static function getAuthToken(): ?string
    {
        return self::$authToken;
    }

    public static function getUserAgent(): string
    {
        return self::$userAgent;
    }

    public static function getImageDriver(): ?ImageDriver
    {
        if (!isset(self::$imageDriver)) {
            throw new RuntimeException('Image driver not set. Please set the image driver using `Transformers::setup()->setImageDriver()`');
        }

        return self::$imageDriver;
    }

    public static function getLogger(): ?LoggerInterface
    {
        return self::$logger;
    }
}
