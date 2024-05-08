<?php

namespace Codewithkyrian\Transformers\OnnxRuntime;

use Codewithkyrian\Transformers\Libraries;

class FFI
{
    public static mixed $lib;
    private static \FFI $instance;

    public static function instance(): \FFI
    {
        if (!isset(self::$instance)) {
            $headerCode = file_get_contents(Libraries::OnnxRuntime->headerFile());
            self::$instance = \FFI::cdef($headerCode, Libraries::OnnxRuntime->libFile());
        }

        return self::$instance;
    }

    public static function libVersion(): string
    {
        return (self::instance()->OrtGetApiBase()[0]->GetVersionString)();
    }

    private static \FFI $libc;

    // for Windows
    public static function libc(): \FFI
    {
        if (!isset(self::$libc)) {
            self::$libc = \FFI::cdef(
                'size_t mbstowcs(void *wcstr, const char *mbstr, size_t count);',
                'msvcrt.dll'
            );
        }

        return self::$libc;
    }
}
