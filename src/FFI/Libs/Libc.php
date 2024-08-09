<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI\Libs;

use FFI;
use FFI\CData;
use RuntimeException;

class Libc
{
    protected static FFI $ffi;

    public static function version(): string
    {
        return '1.0.0';
    }


    /**
     * Returns an instance of the FFI class after checking if it has already been instantiated.
     * If not, it creates a new instance by defining the header contents and library path.
     *
     * @return FFI The FFI instance.
     */
    protected static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            self::$ffi = match (PHP_OS_FAMILY) {
                'Windows' => FFI::cdef(
                    "\nsize_t mbstowcs(void *wcstr, const char *mbstr, size_t count);",
                    'msvcrt.dll'
                ),
                default => FFI::cdef()
            };
        }

        return self::$ffi;
    }

    public static function new($type, bool $owned = true, bool $persistent = false): ?CData
    {
        return self::ffi()->new($type, $owned, $persistent);
    }

    public static function mbStringToWcString(CData $wcStr, string $mbStr, int $count): CData
    {
        $length = self::ffi()->mbstowcs($wcStr, $mbStr, $count);

        if ($length != strlen($mbStr)) {
            throw new RuntimeException('Expected mbstowcs to return '.strlen($mbStr).", got $length");
        }

        return $wcStr;
    }

    public static function cstring($str): CData
    {
        $bytes = strlen($str) + 1;
        // TODO fix?
        $ptr = self::new("char[$bytes]", owned: false);
        FFI::memcpy($ptr, $str, $bytes - 1);
        $ptr[$bytes - 1] = "\0";

        return $ptr;
    }
}