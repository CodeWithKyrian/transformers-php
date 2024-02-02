<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Utility class to download files from the Hugging Face Hub
 */
class Hub
{

    private const DEFAULT_CACHE_DIR = '.cache';

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
     * @param string|null $token The token to use as an authorization to download from private model repos.
     * @param string $revision The specific model version to use. It can be a branch name, a tag name,
     * or a commit id. Defaults to 'main'.
     * @param string $subFolder In case the relevant files are located inside a subfolder of the model repo or
     * directory, indicate it here.
     * @param bool $fatal Whether to raise an error if the file could not be loaded.
     *
     * @throws Exception
     */
    public static function getFile(
        string  $pathOrRepoID,
        string  $fileName,
        ?string $cacheDir = null,
        ?string $token = null,
        string  $revision = 'main',
        string  $subFolder = '',
        bool    $fatal = true
    ): ?string
    {
        $cacheDir ??= self::DEFAULT_CACHE_DIR;
        $localCacheDir = self::joinPaths($cacheDir, $pathOrRepoID, $subFolder);
        $fullFilePath = self::joinPaths($subFolder, $fileName);
        $fileLocalPath = self::joinPaths($localCacheDir, $fileName);

        if (is_dir($localCacheDir)) {
            if (file_exists($fileLocalPath)) {
                echo "Found local copy of $fullFilePath \n";
                return $fileLocalPath;
            }
        }


        // Since Guzzle 'sink' option expects the folder to already exist, we create it if it doesn't
        $pathParts = explode(DIRECTORY_SEPARATOR, $fileLocalPath, -1);
        $currentPath = '';
        foreach ($pathParts as $pathPart) {
            $currentPath = self::joinPaths($currentPath, $pathPart);
            if (!is_dir($currentPath)) {
                mkdir($currentPath, 0755);
            }
        }

        $repositoryURL = "https://huggingface.co/{$pathOrRepoID}/resolve/{$revision}/{$fullFilePath}";

        echo "Downloading $fullFilePath from $repositoryURL \n";

        try {
            $client = new Client([
                'headers' => [
                    'User-Agent' => 'transformers-php',
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);

            $client->get($repositoryURL, [
                'sink' => $fileLocalPath,
            ]);

            return $fileLocalPath;
        } catch (GuzzleException $e) {

            // Delete the file if it was created (guzzle 'sink' option creates a file even if the request fails)
            if (file_exists($fileLocalPath)) {
                unlink($fileLocalPath);
            }

            self::handleError($e->getCode(), $repositoryURL, $fatal);
        }

        return null;
    }

    public static function getJson(
        string  $pathOrRepoID,
        string  $fileName,
        ?string $cacheDir = null,
        ?string $token = null,
        string  $revision = 'main',
        string  $subFolder = '',
        bool    $fatal = true
    ): ?array
    {
        $file = self::getFile($pathOrRepoID, $fileName, $cacheDir, $token, $revision, $subFolder, $fatal);

        if ($file === null) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    public static function joinPaths(): ?string
    {
        $paths = array();

        foreach (func_get_args() as $arg) {
            if ($arg !== '') {
                $paths[] = $arg;
            }
        }

        return preg_replace('#/+#', '/', join(DIRECTORY_SEPARATOR, $paths));
    }

    /**
     * @throws Exception
     */
    private static function handleError(int $statusCode, string $remoteURL, bool $fatal = true): void
    {
        if (!$fatal) {
            // File was not loaded correctly, but it is optional.
            // TODO in future, cache the response?
            return;
        }

        $message = self::ERROR_MAPPING[$statusCode] ?? "Error $statusCode occurred while trying to load file from $remoteURL";

        throw new Exception($message, $statusCode);
    }

}