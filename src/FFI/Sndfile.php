<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FFI;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\TransformersLibrariesDownloader\Libraries;
use FFI;
use FFI\CData;
use RuntimeException;

class Sndfile
{
    private static FFI $ffi;

    private static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            $headerCode = file_get_contents(Libraries::Sndfile->headerFile(Transformers::$libsDir));
            self::$ffi = FFI::cdef($headerCode, Libraries::Sndfile->libFile(Transformers::$libsDir));
        }

        return self::$ffi;
    }

    public static function new($type, bool $owned = true, bool $persistent = false): ?CData
    {
        return self::ffi()->new($type, $owned, $persistent);
    }

    public static function enum(string $name)
    {
        return self::ffi()->{$name};
    }

    /**
     * Open the specified file for read, write or both.
     * @param string $path
     * @param int $mode
     * @param CData $sfinfo
     * @return mixed
     */
    public static function open(string $path, int $mode, CData $sfinfo): mixed
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $sndfile = self::ffi()->sf_wchar_open($path, $mode, $sfinfo);
        } else {
            $sndfile = self::ffi()->sf_open($path, $mode, $sfinfo);
        }

        if ($sndfile === null) {
            $error = self::ffi()->sf_strerror($sndfile);
            throw new RuntimeException("Failed to open file: $error");
        }

        return $sndfile;
    }

    public static function getFormat(mixed $sndfile, mixed $sfinfo): string
    {
        $info = self::ffi()->new('SF_FORMAT_INFO');

        $info->format = $sfinfo->format;

        self::ffi()->sf_command($sndfile, self::ffi()->SFC_GET_FORMAT_INFO, FFI::addr($info), FFI::sizeof($info));

        return $info->name;
    }

    public static function readFrames(CData $sndfile, CData $ptr, int $frames, string $type = 'float'): int
    {
        return match ($type) {
            'int' => self::ffi()->sf_readf_int($sndfile, $ptr, $frames),
            'float' => self::ffi()->sf_readf_float($sndfile, $ptr, $frames),
            'double' => self::ffi()->sf_readf_double($sndfile, $ptr, $frames),
        };
    }

    public static function writeFrames(CData $sndfile, CData $ptr, int $frames, string $type = 'float'): int
    {
        return match ($type) {
            'int' => self::ffi()->sf_writef_int($sndfile, $ptr, $frames),
            'float' => self::ffi()->sf_writef_float($sndfile, $ptr, $frames),
            'double' => self::ffi()->sf_writef_double($sndfile, $ptr, $frames),
        };
    }

    public static function version(): string
    {
        return self::ffi()->sf_version_string();
    }

    public static function close(CData $sndfile): void
    {
        self::ffi()->sf_close($sndfile);
    }
}