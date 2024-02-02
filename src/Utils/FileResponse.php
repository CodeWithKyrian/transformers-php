<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use SplFileObject;

class FileResponse
{
    /**
     * Mapping from file extensions to MIME types.
     */
    private const CONTENT_TYPE_MAP = [
        'txt' => 'text/plain',
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
    ];

    public string $filePath;

    public bool $exists;

    protected int $status = 200;

    protected string $statusText = 'OK';

    protected SplFileObject|null $file;

    protected array $headers = [];


    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        $this->exists = file_exists($this->filePath);

        if ($this->exists) {
            $this->status = 200;
            $this->statusText = 'OK';

            $this->file = new SplFileObject($this->filePath, 'RB');

            $this->headers['Content-Type'] = $this->getMimeType($this->filePath);
            $this->headers['Content-Length'] = (string)$this->file->getSize();
        } else {
            $this->status = 404;
            $this->statusText = 'Not Found';

            $this->file = null;
        }
    }

    public function clone(): FileResponse
    {
        $response = new FileResponse($this->filePath);
        $response->exists = $this->exists;
        $response->status = $this->status;
        $response->statusText = $this->statusText;
        $response->headers = $this->headers;
        return $response;
    }

    public function getMimeType(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return self::CONTENT_TYPE_MAP[$extension] ?? 'application/octet-stream';
    }

    public function getBody(): string
    {
        return $this->file->fread(filesize($this->filePath));
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function blob(): SplFileObject
    {
        return $this->file;
    }

    public function text(): string
    {
        return (string)$this->file;
    }

    public function json(): array
    {
        return json_decode(json_encode($this->text()), true); // Assuming JSON decoding is supported for this specific use case
    }
}