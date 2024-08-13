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

        $baseUrl = "https://github.com/CodeWithKyrian/transformers-php/releases/download/$version";
        $downloadFile = "transformersphp-$version-$os-$arch.$extension";
        $downloadUrl = "$baseUrl/$downloadFile";
        $downloadPath = tempnam(sys_get_temp_dir(), 'transformers-php').".$extension";

        echo "  - Downloading ".self::colorize($downloadFile)."\n";
        Downloader::download($downloadUrl, $downloadPath);
        echo "  - Installing ".self::colorize($downloadFile)." : Extracting archive\n";

        $archive = new \PharData($downloadPath);
        if ($extension != 'zip') {
            $archive = $archive->decompress();
        }

        $archive->extractTo($libsDir, overwrite: true);

        @unlink($downloadPath);

        echo "TransformersPHP libraries installed\n";
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