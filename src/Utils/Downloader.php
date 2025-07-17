<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

class Downloader
{
    private static array $options = [
        'http' => [
            'method' => CURLOPT_CUSTOMREQUEST,
            'content' => CURLOPT_POSTFIELDS,
            'header' => CURLOPT_HTTPHEADER,
            'timeout' => CURLOPT_TIMEOUT,
            'User-agent' => CURLOPT_USERAGENT,
        ],
        'ssl' => [
            'cafile' => CURLOPT_CAINFO,
            'capath' => CURLOPT_CAPATH,
            'verify_peer' => CURLOPT_SSL_VERIFYPEER,
            'verify_peer_name' => CURLOPT_SSL_VERIFYHOST,
            'local_cert' => CURLOPT_SSLCERT,
            'local_pk' => CURLOPT_SSLKEY,
            'passphrase' => CURLOPT_SSLKEYPASSWD,
        ],
    ];

    /**
     * Copy a file synchronously
     *
     * @param string $url URL to download
     * @param string $to Path to copy to
     * @param array $options Stream context options e.g. https://www.php.net/manual/en/context.http.php
     *                                     although not all options are supported when using the default curl downloader
     * @param callable|null $onProgress Callback to notify about the download progress
     * @return false|string
     */
    public static function download(string $url, string $to, array $options = [], ?callable $onProgress = null): bool|string
    {
        $curlHandle = curl_init();

        $headerHandle = fopen('php://temp/maxmemory:32768', 'w+b');
        if (false === $headerHandle) {
            throw new \RuntimeException('Failed to open a temp stream to store curl headers');
        }

        $errorMessage = '';
        set_error_handler(static function ($code, $msg) use (&$errorMessage): void {
            if ($errorMessage) {
                $errorMessage .= "\n";
            }
            $errorMessage .= preg_replace('{^fopen\(.*?\): }', '', $msg);
        });

        $bodyHandle = fopen($to, 'w+b');
        restore_error_handler();
        if (false === $bodyHandle) {
            throw new \Exception("The \"$url\" file could not be written to $to: $errorMessage");
        }

        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, max((int)ini_get("default_socket_timeout"), 300));
        curl_setopt($curlHandle, CURLOPT_WRITEHEADER, $headerHandle);
        curl_setopt($curlHandle, CURLOPT_FILE, $bodyHandle);
        curl_setopt($curlHandle, CURLOPT_ENCODING, ""); // let cURL set the Accept-Encoding header to what it supports
        curl_setopt($curlHandle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

        if (!isset($options['http']['header'])) {
            $options['http']['header'] = [];
        }

        $options['http']['header'] = array_diff($options['http']['header'], ['Connection: close']);
        $options['http']['header'][] = 'Connection: keep-alive';

        $version = curl_version();
        $features = $version['features'];
        if (str_starts_with($url, 'https://') && \defined('CURL_VERSION_HTTP2') && \defined('CURL_HTTP_VERSION_2_0') && (CURL_VERSION_HTTP2 & $features) !== 0) {
            curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        }

        // curl 8.7.0 - 8.7.1 has a bug whereas automatic accept-encoding header results in an error when reading the response
        // https://github.com/composer/composer/issues/11913
        if (isset($version['version']) && in_array($version['version'], ['8.7.0', '8.7.1'], true) && \defined('CURL_VERSION_LIBZ') && (CURL_VERSION_LIBZ & $features) !== 0) {
            curl_setopt($curlHandle, CURLOPT_ENCODING, "gzip");
        }

        foreach (self::$options as $type => $curlOptions) {
            foreach ($curlOptions as $name => $curlOption) {
                if (isset($options[$type][$name])) {
                    if ($type === 'ssl' && $name === 'verify_peer_name') {
                        curl_setopt($curlHandle, $curlOption, $options[$type][$name] === true ? 2 : $options[$type][$name]);
                    } else {
                        curl_setopt($curlHandle, $curlOption, $options[$type][$name]);
                    }
                }
            }
        }

        $progressFn = static function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($onProgress): void {
            if ($onProgress === null || $downloadSize <= 0) return;
            $onProgress($downloadSize, $downloaded, $uploadSize, $uploaded);
            flush();
        };

        curl_setopt($curlHandle, CURLOPT_NOPROGRESS, false);

        curl_setopt($curlHandle, CURLOPT_PROGRESSFUNCTION, $progressFn);

        if (curl_exec($curlHandle) === false) {
            $error = curl_error($curlHandle);
            curl_close($curlHandle);
            fclose($headerHandle);
            fclose($bodyHandle);
            throw new \Exception("The \"$url\" file could not be downloaded: $error");
        }

        $statusCode = curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);

        if ($statusCode < 200 || $statusCode >= 300) {
            curl_close($curlHandle);
            fclose($headerHandle);
            fclose($bodyHandle);
            throw new \Exception("The \"$url\" file could not be downloaded: HTTP $statusCode");
        }

        curl_close($curlHandle);

        rewind($headerHandle);

        $headers = stream_get_contents($headerHandle);

        fclose($headerHandle);

        fclose($bodyHandle);

        return $headers;
    }
}
