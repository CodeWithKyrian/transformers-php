<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\TransformersLibrariesDownloader\Libraries;

class LibsChecker
{
    public static function check(): void
    {
        echo self::colorize("Checking TransformersPHP libraries.... ") . "\n";

        foreach (Libraries::cases() as $library) {
            if (!$library->exists(Transformers::$libsDir)) {
                $name = $library->folder(Transformers::$libsDir);

                self::downloadLibrary($name);
            }
        }

        echo self::colorize("All TransformersPHP libraries are installed") . "\n";
    }

    private static function downloadLibrary(string $name): void
    {
        $baseUrl = Libraries::baseUrl(Transformers::$libsDir);
        $ext = Libraries::ext();

        $downloadUrl = Libraries::joinPaths($baseUrl, "$name.$ext");
        $downloadPath = tempnam(sys_get_temp_dir(), 'transformers-php') . ".$ext";

        echo "  - Downloading " . self::colorize($name) . "\n";

        Downloader::download($downloadUrl, $downloadPath);

        echo "  - Installing " . self::colorize($name) . " : Extracting archive\n";

        $archive = new \PharData($downloadPath);

        if ($ext != 'zip') {
            $archive = $archive->decompress();
        }

        $archive->extractTo(Transformers::$libsDir);
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