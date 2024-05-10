<?php

/**
 * This file is a modified version of the original file from the onnxruntime-php repository.
 *
 * Original source: https://github.com/ankane/onnxruntime-php/blob/master/src/FFI.php
 * The original file is licensed under the MIT License.
 */

namespace Codewithkyrian\Transformers\OnnxRuntime;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\TransformersLibrariesDownloader\Libraries;

class FFI
{
    public static mixed $lib;
    private static \FFI $instance;

    public static function instance(): \FFI
    {
        if (!isset(self::$instance)) {
            $headerCode = file_get_contents(Libraries::OnnxRuntime->headerFile(Transformers::$libsDir));
            self::$instance = \FFI::cdef($headerCode, Libraries::OnnxRuntime->libFile(Transformers::$libsDir));
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
