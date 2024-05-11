<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\Exceptions\HubException;
use Codewithkyrian\Transformers\Transformers;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Utility class to download files from the Hugging Face Hub
 */
class Hub
{

    private const ERROR_MAPPING = [
        '400' => 'Bad request error occurred while trying to load file',
        '401' => 'Unauthorized access to file',
        '403' => 'Forbidden access to file',
        '404' => 'File not found',
        '408' => 'Timeout error occurred while trying to load file',

        '500' => 'Internal Server Error occurred while trying to load file',
        '502' => 'Bad Gateway error occurred while trying to load file',
        '503' => 'Service Unavailable error occurred while trying to load file',
        '504' => 'Gateway Timeout error occurred while trying to load file',
    ];

    /**
     * Tries to locate a file in a local folder and repo, downloads and cache it if necessary.
     *
     * @param string $pathOrRepoID This can be either a string, the "model id" of a model repo on huggingface.co,
     * or a path to a local directory containing a model.
     * @param string $fileName The name of the file to locate in $pathOrRepoID.
     * @param string|null $cacheDir Path to a directory in which a downloaded pretrained model configuration should
     * be cached if the standard cache should not be used.
     * the cached versions if they exist. Defaults to false.
     * @param string $revision The specific model version to use. It can be a branch name, a tag name,
     * or a commit id. Defaults to 'main'.
     * @param string $subFolder In case the relevant files are located inside a subfolder of the model repo or
     * directory, indicate it here.
     * @param bool $fatal Whether to raise an error if the file could not be loaded.
     *
     * @throws HubException
     */

    public static function getFile(
        string    $pathOrRepoID,
        string    $fileName,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        string    $subFolder = '',
        bool      $fatal = true,
        ?callable $onProgress = null
    ): ?string
    {
        # Local cache and file paths
        $cacheDir ??= Transformers::$cacheDir;

        $filePath = joinPaths($cacheDir, $pathOrRepoID, $subFolder, $fileName);

        # Check if file already exists
        if (file_exists($filePath)) {
            return $filePath;
        }

        $remoteURL = self::resolveRepositoryURL($pathOrRepoID, $revision, $fileName, $subFolder);

        $partCounter = 1;
        $partBasePath = "$filePath.part";

        while (file_exists($partBasePath . $partCounter)) {
            $partCounter++;
        }

        $partPath = $partBasePath . $partCounter;

        # Resume download if partially downloaded
        $downloadedBytes = 0;
        if ($partCounter > 1) {
            for ($i = 1; $i < $partCounter; $i++) {
                $downloadedBytes += filesize($partBasePath . $i);
            }
        }

        # Create directory structure if needed
        ensureDirectory($filePath);

        $options = [
            'http' => [
                'header' => [
                    'Range: bytes=' . $downloadedBytes . '-',
                ],
                'User-Agent' => Transformers::$userAgent,
            ],
        ];

        if (Transformers::$authToken) {
            $options['http']['header'][] = 'Authorization: Bearer ' . Transformers::$authToken;
        }

        try {
            if ($onProgress) {
                $onProgress('begin_download', $fileName, 0, 0, 0, 0);
            }

            $progressCallback = function ($downloadSize, $downloaded, $uploadSize, $uploaded) use ($onProgress, $fileName) {
                if ($onProgress) {
                    $onProgress('advance_download', $fileName, $downloadSize, $downloaded, $uploadSize, $uploaded);
                }
            };

            Downloader::download($remoteURL, $partPath, $options, $progressCallback);

            if ($onProgress) {
                $onProgress('complete_download', $fileName, 0, 0, 0, 0);
            }

            # Combine part files if necessary
            if ($partCounter > 1) {
                self::combinePartFiles($filePath, $partBasePath, $partCounter);
            } else {
                rename($partPath, $filePath);
            }

            return $filePath;
        } catch (Exception $e) {
            self::handleException($e->getCode(), $remoteURL, $fatal);
        }

        return null;
    }

    /**
     * @throws HubException
     */
    public static function getJson(
        string    $pathOrRepoID,
        string    $fileName,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        string    $subFolder = '',
        bool      $fatal = true,
        ?callable $onProgress = null
    ): ?array
    {
        $file = self::getFile($pathOrRepoID, $fileName, $cacheDir, $revision, $subFolder, $fatal, $onProgress);

        if ($file === null) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }


    private static function onProgress(ProgressBar $progressBar): callable
    {
        return function ($totalDownload, $downloadedBytes) use ($progressBar) {
            if ($totalDownload == 0) return;

            $percent = round(($downloadedBytes / $totalDownload) * 100, 2);
            $progressBar->setProgress((int)$percent);
        };
    }


    public static function combinePartFiles($filePath, $partBasePath, $partCount): void
    {
        $fileHandle = fopen($filePath, 'w');
        for ($i = 1; $i <= $partCount; $i++) {
            $partPath = $partBasePath . $i;
            $partFileHandle = fopen($partPath, 'r');
            stream_copy_to_stream($partFileHandle, $fileHandle);
            fclose($partFileHandle);
            unlink($partPath);
        }
        fclose($fileHandle);
    }


    /**
     * @throws HubException
     */
    private static function handleException(int $statusCode, string $remoteURL, bool $fatal = true): void
    {
        if (!$fatal) {
            // File was not loaded correctly, but it is optional.
            // TODO in future, cache the response?
            return;
        }

        $message = self::ERROR_MAPPING[$statusCode] ?? "Error $statusCode occurred while trying to load file from $remoteURL";

        throw new HubException($message, $statusCode);
    }

    private static function resolveRepositoryURL(string $pathOrRepoID, string $revision, string $fileName, string $subFolder): string
    {
        $remoteHost = Transformers::$remoteHost;

        $remotePath = str_replace(
            ['{model}', '{revision}', '{file}'],
            [$pathOrRepoID, $revision, joinPaths($subFolder, $fileName)],
            Transformers::$remotePathTemplate
        );

        return joinPaths($remoteHost, $remotePath);
    }
}