<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\TransformersLibrariesDownloader\Libraries;

class LibsChecker
{
    public static function check(): void
    {
        echo "Checking TransformersPHP libraries...\n";

        foreach (Libraries::cases() as $library) {
            if (!$library->exists(Transformers::$libsDir)) {
                $name = $library->folder(Transformers::$libsDir);

                self::downloadLibrary($name);
            }
        }

        echo "All TransformersPHP libraries are installed\n";
    }

    private static function downloadLibrary(string $name): void
    {
        $baseUrl = Libraries::baseUrl(Transformers::$libsDir);
        $ext = Libraries::ext();

        $downloadUrl = Libraries::joinPaths($baseUrl, "$name.$ext");
        $downloadPath = tempnam(sys_get_temp_dir(), 'transformers-php') . ".$ext";

        echo "  - Downloading $name\n";

        Downloader::download($downloadUrl, $downloadPath);

        echo "  - Installing $name : Extracting archive\n";

        $archive = new \PharData($downloadPath);

        if ($ext != 'zip') {
            $archive = $archive->decompress();
        }

        $archive->extractTo(Transformers::$libsDir);
    }
}