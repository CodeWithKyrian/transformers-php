<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use UnexpectedValueException;
use function dirname;
use function ini_get;
use function is_resource;
use function is_string;

class StreamLogger implements LoggerInterface
{
    use LoggerTrait;

    protected const LOG_FORMAT = "[%datetime%] whisper.%level_name%: %message% %context%\n";

    protected const DATE_FORMAT = 'Y-m-d H:i:s';

    protected const MAX_CHUNK_SIZE = 2147483647;

    protected const DEFAULT_CHUNK_SIZE = 10 * 1024 * 1024; // 10MB

    protected int $streamChunkSize;

    /** @var ?resource */
    protected $stream;

    protected ?string $url = null;

    private ?string $errorMessage = null;

    protected ?int $filePermission;

    protected bool $useLocking;

    protected string $fileOpenMode;

    /** @var true|null */
    private ?bool $dirCreated = null;

    private bool $retrying = false;

    /**
     * @param  resource|string  $stream  If a missing path can't be created, an UnexpectedValueException will be thrown on first write
     * @param  int|null  $filePermission  Optional file permissions (default (0644) are only for owner read/write)
     * @param  bool  $useLocking  Try to lock log file before doing any writes
     * @param  string  $fileOpenMode  The fopen() mode used when opening a file, if $stream is a file path
     *
     * @throws InvalidArgumentException If stream is not a resource or string
     */
    public function __construct(mixed $stream, ?int $filePermission = null, bool $useLocking = false, string $fileOpenMode = 'a')
    {

        if (($phpMemoryLimit = self::getMemoryLimitInBytes()) !== false) {
            if ($phpMemoryLimit > 0) {
                // use max 10% of allowed memory for the chunk size, and at least 100KB
                $this->streamChunkSize = min(static::MAX_CHUNK_SIZE, max((int) ($phpMemoryLimit / 10), 100 * 1024));
            } else {
                // memory is unlimited, set to the default 10MB
                $this->streamChunkSize = static::DEFAULT_CHUNK_SIZE;
            }
        } else {
            // no memory limit information, set to the default 10MB
            $this->streamChunkSize = static::DEFAULT_CHUNK_SIZE;
        }

        if (is_resource($stream)) {
            $this->stream = $stream;

            stream_set_chunk_size($this->stream, $this->streamChunkSize);
        } elseif (is_string($stream)) {
            $this->url = self::canonicalizePath($stream);
        } else {
            throw new InvalidArgumentException('A stream must either be a resource or a string.');
        }

        $this->fileOpenMode = $fileOpenMode;
        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        if (! is_resource($this->stream)) {
            $url = $this->url;
            if ($url === null || $url === '') {
                throw new LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close()');
            }
            $this->createDir($url);
            $this->errorMessage = null;
            set_error_handler($this->customErrorHandler(...));

            try {
                $stream = fopen($url, $this->fileOpenMode);
                if ($this->filePermission !== null) {
                    @chmod($url, $this->filePermission);
                }
            } finally {
                restore_error_handler();
            }
            if (! is_resource($stream)) {
                $this->stream = null;

                throw new UnexpectedValueException('The stream could not be opened in append mode');
            }
            stream_set_chunk_size($stream, $this->streamChunkSize);
            $this->stream = $stream;
        }

        $stream = $this->stream;
        if ($this->useLocking) {
            // ignoring errors here, there's not much we can do about them
            flock($stream, LOCK_EX);
        }

        $this->errorMessage = null;
        set_error_handler($this->customErrorHandler(...));
        try {

            $params = [
                '%datetime%' => date(static::DATE_FORMAT),
                '%level_name%' => $level,
                '%message%' => trim($message),
                '%context%' => json_encode(
                    $context,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE |
                    JSON_PRESERVE_ZERO_FRACTION
                ),
            ];
            fwrite($stream, strtr(static::LOG_FORMAT, $params));
        } finally {
            restore_error_handler();
        }
        if ($this->errorMessage !== null) {
            // close the resource if possible to reopen it, and retry the failed write
            if (! $this->retrying && $this->url !== null && $this->url !== 'php://memory') {
                $this->retrying = true;
                $this->close();
                $this->log($level, $message, $context);

                return;
            }

            throw new UnexpectedValueException('Writing to the log file failed');
        }

        $this->retrying = false;
        if ($this->useLocking) {
            flock($stream, LOCK_UN);
        }
    }

    public function close(): void
    {
        if ($this->url !== null && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
        $this->dirCreated = null;
    }

    private function getDirFromStream(string $stream): ?string
    {
        $pos = strpos($stream, '://');
        if ($pos === false) {
            return dirname($stream);
        }

        if (str_starts_with($stream, 'file://')) {
            return dirname(substr($stream, 7));
        }

        return null;
    }

    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir|fwrite)\(.*?\): }', '', $msg);

        return true;
    }

    private function createDir(string $url): void
    {
        // Do not try to create dir if it has already been tried.
        if ($this->dirCreated === true) {
            return;
        }

        $dir = $this->getDirFromStream($url);
        if ($dir !== null && ! is_dir($dir)) {
            $this->errorMessage = null;
            set_error_handler(function (...$args) {
                return $this->customErrorHandler(...$args);
            });
            $status = mkdir($dir, 0777, true);
            restore_error_handler();
            if ($status === false && ! is_dir($dir) && ! str_contains((string) $this->errorMessage, 'File exists')) {
                throw new UnexpectedValueException(sprintf('There is no existing directory at "%s" and it could not be created: '.$this->errorMessage, $dir));
            }
        }
        $this->dirCreated = true;
    }

    protected static function getMemoryLimitInBytes(): false|int
    {
        $limit = ini_get('memory_limit');
        if (! is_string($limit)) {
            return false;
        }

        // support -1
        if ((int) $limit < 0) {
            return (int) $limit;
        }

        if (!preg_match('/^\s*(?<limit>\d+)(?:\.\d+)?\s*(?<unit>[gmk]?)\s*$/i', $limit, $match)) {
            return false;
        }

        $limit = (int) $match['limit'];
        switch (strtolower($match['unit'])) {
            case 'g':
                $limit *= 1024;
            // no break
            case 'm':
                $limit *= 1024;
            // no break
            case 'k':
                $limit *= 1024;
        }

        return $limit;
    }

    /**
     * Makes sure if a relative path is passed in it is turned into an absolute path
     *
     * @param  string  $streamUrl  stream URL or path without protocol
     */
    public static function canonicalizePath(string $streamUrl): string
    {
        $prefix = '';
        if (str_starts_with($streamUrl, 'file://')) {
            $streamUrl = substr($streamUrl, 7);
            $prefix = 'file://';
        }

        // other type of stream, not supported
        if (str_contains($streamUrl, '://')) {
            return $streamUrl;
        }

        // already absolute
        if (str_starts_with($streamUrl, '/') || substr($streamUrl, 1, 1) === ':' || str_starts_with($streamUrl, '\\\\')) {
            return $prefix.$streamUrl;
        }

        $streamUrl = getcwd().'/'.$streamUrl;

        return $prefix.$streamUrl;
    }

    public function __destruct()
    {
        $this->close();
    }
}
