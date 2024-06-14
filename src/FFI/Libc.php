<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FFI;

use FFI;
use FFI\CData;
use http\Exception\RuntimeException;

class Libc
{
    private static FFI $ffi;

    public static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            if (PHP_OS_FAMILY == 'Windows') {
                self::$ffi = FFI::cdef(
                    'size_t mbstowcs(void *wcstr, const char *mbstr, size_t count);',
                    'msvcrt.dll'
                );
            }
            else{
                self::$ffi = FFI::cdef();
            }
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
            throw new RuntimeException('Expected mbstowcs to return ' . strlen($mbStr) . ", got $length");
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