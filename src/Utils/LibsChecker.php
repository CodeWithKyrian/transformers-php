<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\TransformersLibrariesDownloader\Library;
use Composer\InstalledVersions;

class LibsChecker
{
    public static function check($event): void
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir.'/autoload.php';

        $libsDir = basePath('libs');
        $installationNeeded = false;

        foreach (Library::cases() as $library) {
            if (!$library->exists($libsDir)) {
                $installationNeeded = true;
                break;
            }
        }

        if ($installationNeeded) {
            echo self::colorize("Installing TransformersPHP libraries...")."\n";
            self::install($libsDir);
        }
    }

    private static function install(string $libsDir): void
    {
        $version = file_get_contents(basePath('VERSION'));

        $os = match (PHP_OS_FAMILY) {
            'Windows' => 'windows',
            'Darwin' => 'macosx',
            default => 'linux',
        };

        $arch = match (PHP_OS_FAMILY) {
            'Windows' => 'x86_64',
            'Darwin' => php_uname('m') == 'x86_64' ? 'x86_64' : 'arm64',
            default => php_uname('m') == 'x86_64' ? 'x86_64' : 'aarch64',
        };

        $extension = match ($os) {
            'windows' => 'zip',
            default => 'tar.gz',
        };

        $maxRetries = 10;
        $attempts = 0;

        do {
            $baseUrl = "https://github.com/CodeWithKyrian/transformers-php/releases/download/$version";
            $downloadFile = "transformersphp-$version-$os-$arch.$extension";
            $downloadUrl = "$baseUrl/$downloadFile";
            $downloadPath = tempnam(sys_get_temp_dir(), 'transformers-php').".$extension";

            echo "  - Downloading ".self::colorize("transformersphp-$version-$os-$arch")."\n";

            $downloadSuccess = false;

            try {
                $downloadSuccess = Downloader::download($downloadUrl, $downloadPath);
            } catch (\Exception) {
            }

            if ($downloadSuccess) {
                echo "  - Installing ".self::colorize("transformersphp-$version-$os-$arch")." : Extracting archive\n";

                $archive = new \PharData($downloadPath);
                if ($extension != 'zip') {
                    $archive = $archive->decompress();
                }

                $archive->extractTo($libsDir, overwrite: true);
                @unlink($downloadPath);

                echo "TransformersPHP libraries installed\n";
                return;
            } else {
                echo "  - Failed to download transformersphp-$version-$os-$arch, trying a lower version...\n";
                $version = self::getLowerVersion($version);
            }

            $attempts++;
        } while ($version !== null && $attempts < $maxRetries);

        throw new \Exception("Could not find the required binaries after $maxRetries attempts.");
    }

    private static function getLowerVersion(string $version): ?string
    {
        $parts = explode('.', $version);

        if (count($parts) === 3 && $parts[2] > 0) {
            $parts[2]--;
        } elseif (count($parts) === 3) {
            $parts[1]--;
            $parts[2] = 9;  // Reset patch version
        } elseif (count($parts) === 2 && $parts[1] > 0) {
            $parts[1]--;
        } else {
            return null;  // No lower version possible
        }

        return implode('.', $parts);
    }

    private static function colorize(string $text, string $color = 'green'): string
    {
        $prefix = match ($color) {
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
        };

        return "$prefix$text\033[39m";
    }
}