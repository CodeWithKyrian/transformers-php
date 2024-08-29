<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\TransformersLibsLoader\Library;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class LibsChecker
{
    protected static ProgressBar $progressBar;

    protected static function getProgressBar($filename, $output): ProgressBar
    {
        ProgressBar::setFormatDefinition('hub', '  - Downloading <info>%message%</info> : [%bar%] %percent:3s%%');

        if (!isset(self::$progressBar)) {
            self::$progressBar = new ProgressBar($output, 100);
            self::$progressBar->setFormat('hub');
            self::$progressBar->setBarCharacter('<fg=green>•</>');
            self::$progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
            self::$progressBar->setProgressCharacter('<fg=green>➤</>');
            self::$progressBar->setMessage($filename);
        }

        return self::$progressBar;
    }

    public static function check($event = null, OutputInterface $output = null): void
    {
        $output ??= new ConsoleOutput();

        $vendorDir = $event !== null ?
            $event->getComposer()->getConfig()->get('vendor-dir')
            : 'vendor';

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
            $output->writeln("<info>Installing TransformersPHP libraries...</info>");
            self::install($output);
        }
    }

    private static function install(OutputInterface $output): void
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
            $filename = "transformersphp-$version-$os-$arch";
            $downloadUrl = "$baseUrl/$filename.$extension";
            $downloadPath = tempnam(sys_get_temp_dir(), 'transformers-php').".$extension";

            $onProgress = function ($downloadSize, $downloaded, $uploadSize, $uploaded) use ($output, $filename) {
                $progressBar = self::getProgressBar($filename, $output);
                $percent = round(($downloaded / $downloadSize) * 100, 2);
                $progressBar->setProgress((int)$percent);
            };

            $downloadSuccess = false;

            try {
                $downloadSuccess = Downloader::download($downloadUrl, $downloadPath, onProgress: $onProgress);

                $progressBar = self::getProgressBar($filename, $output);
                $progressBar->finish();
                $progressBar->clear();
                $output->writeln("  - Downloading <info>$filename</info>");
            } catch (\Exception) {
            } finally {
                unset($progressBar);
            }

            if ($downloadSuccess) {
                $output->writeln("  - Installing <info>$filename</info> : Extracting archive");

                $archive = new \PharData($downloadPath);
                if ($extension != 'zip') {
                    $archive = $archive->decompress();
                }

                $archive->extractTo(basePath(), overwrite: true);
                @unlink($downloadPath);

                $output->writeln("✔ TransformersPHP libraries installed successfully!");
                return;
            } else {
                $output->writeln("  - Failed to download <info>$filename</info> trying a lower version...");
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