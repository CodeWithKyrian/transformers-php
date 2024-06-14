<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FFI;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\TransformersLibrariesDownloader\Libraries;
use FFI;
use FFI\CData;
use RuntimeException;

class Samplerate
{
    private static FFI $ffi;

    private static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            $headerCode = file_get_contents(Libraries::Samplerate->headerFile(Transformers::$libsDir));
            self::$ffi = FFI::cdef($headerCode, Libraries::Samplerate->libFile(Transformers::$libsDir));
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
     * Standard initialisation function.
     */
    public static function srcNew(int $converterType, int $channels)
    {
        $error = FFI::new('int32_t');

        $state = self::ffi()->src_new($converterType, $channels, FFI::addr($error));

        if ($error->cdata !== 0) {
            $error = self::ffi()->src_strerror($error);
            throw new RuntimeException("Failed to create sample rate converter: $error");
        }

        return $state;
    }


    /**
     * Standard processing function.
     */
    public static function srcProcess(CData $state, CData $data): void
    {
        $error = self::ffi()->src_process($state, $data);

        if ($error !== 0) {
            $error = self::ffi()->src_strerror($error);
            throw new RuntimeException("Failed to convert sample rate: $error");
        }
    }

    public static function version(): string
    {
        return self::ffi()->src_get_version();
    }

    /**
     * Cleanup all internal allocations.
     */
    public static function srcDelete(CData $state): void
    {
        self::ffi()->src_delete($state);
    }
}