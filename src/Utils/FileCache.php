<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use GuzzleHttp\Psr7\Response;

class FileCache
{
    public function __construct(
        protected string $path
    )
    {
    }

    /**
     * Checks whether the given request is in the cache.
     * @param string $request
     * @return FileResponse|null
     */
    public function match(string $request): ?FileResponse {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $request;
        if (file_exists($filePath)) {
            return new FileResponse($filePath);
        } else {
            return null;
        }
    }

    /**
     * Adds the given response to the cache.
     * @param string $request
     * @param Response|FileResponse $response
     * @return void
     */
    public function put(string $request, Response|FileResponse $response): void {
        $outputPath = $this->path . DIRECTORY_SEPARATOR . $request;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }

        file_put_contents($outputPath, $response->getBody());
    }
}