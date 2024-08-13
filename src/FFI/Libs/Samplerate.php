<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI\Libs;

use Codewithkyrian\Transformers\FFI\Lib;
use Codewithkyrian\Transformers\Transformers;
use Exception;
use FFI;
use FFI\CData;
use FFI\CType;
use RuntimeException;
use function Codewithkyrian\Transformers\Utils\joinPaths;

class Samplerate
{
    protected static FFI $ffi;


    /**
     * Returns an instance of the FFI class after checking if it has already been instantiated.
     * If not, it creates a new instance by defining the header contents and library path.
     *
     * @return FFI The FFI instance.
     * @throws Exception
     */
    protected static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            self::$ffi = FFI::cdef(
                file_get_contents(Lib::Samplerate->header()),
                Lib::Samplerate->library()
            );
        }

        return self::$ffi;
    }

    /**
     * Creates a new instance of the specified type.
     *
     * @param string $type The type of the instance to create.
     * @param bool $owned Whether the instance should be owned. Default is true.
     * @param bool $persistent Whether the instance should be persistent. Default is false.
     *
     * @return CData|null The created instance, or null if the creation failed.
     * @throws Exception
     */
    public static function new(string $type, bool $owned = true, bool $persistent = false): ?CData
    {
        return self::ffi()->new($type, $owned, $persistent);
    }

    /**
     * Casts a pointer to a different type.
     *
     * @param CType|string $type The type to cast to.
     * @param CData|int|float|bool|null $ptr The pointer to cast.
     *
     * @return ?CData The cast pointer, or null if the cast failed.
     * @throws Exception
     */
    public static function cast(CType|string$type, CData|int|float|bool|null$ptr): ?CData
    {
        return self::ffi()->cast($type, $ptr);
    }

    /**
     * Retrieves the value of the enum constant with the given name.
     *
     * @param string $name The name of the enum constant.
     *
     * @return mixed The value of the enum constant.
     * @throws Exception
     */
    public static function enum(string $name): mixed
    {
        return self::ffi()->{$name};
    }

    /**
     * Returns the version of the library as a string.
     *
     * @return string The version of the library.
     */
    public static function version(): string
    {
        return self::ffi()->src_get_version();
    }

    /**
     * Creates a new sample rate converter.
     *
     * @param int $converterType The type of the converter.
     * @param int $channels The number of channels.
     *
     * @return CData The state of the created converter.
     * @throws RuntimeException|Exception If the converter fails to create.
     */
    public static function srcNew(int $converterType, int $channels): CData
    {
        $error = self::new('int32_t');

        $state = self::ffi()->src_new($converterType, $channels, FFI::addr($error));

        if ($error->cdata !== 0) {
            $error = self::ffi()->src_strerror($error);
            throw new RuntimeException("Failed to create sample rate converter: $error");
        }

        return $state;
    }


    /**
     * Processes the given data using the specified sample rate converter state.
     *
     * @param CData $state The state of the sample rate converter.
     * @param CData $data The data to be processed.
     *
     * @return void
     * @throws RuntimeException|Exception If the sample rate conversion fails.
     */
    public static function srcProcess(CData $state, CData $data): void
    {
        $error = self::ffi()->src_process($state, $data);

        if ($error !== 0) {
            $error = self::ffi()->src_strerror($error);
            throw new RuntimeException("Failed to convert sample rate: $error");
        }
    }

    /**
     * Cleanup all internal allocations.
     */
    public static function srcDelete(CData $state): void
    {
        self::ffi()->src_delete($state);
    }
}