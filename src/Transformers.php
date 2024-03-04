<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers;

class Transformers
{
    public static string $defaultCacheDir = 'models';

    public static string $remoteHost = 'https://huggingface.co';

    public static string $remotePathTemplate = '{model}/resolve/{revision}/{file}';

    public static function configure(): static
    {
        return new static;
    }

    /**
     * Set the default cache directory for transformers models and tokenizers
     * @param string $cacheDir
     * @return $this
     */
    public function setCacheDir(string $cacheDir): static
    {
        self::$defaultCacheDir = $cacheDir;

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
}