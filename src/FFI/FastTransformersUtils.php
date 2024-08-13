<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

use Codewithkyrian\TransformersLibrariesDownloader\Library;
use Exception;
use FFI;
use FFI\CData;
use FFI\CType;
use function Codewithkyrian\Transformers\Utils\basePath;

class FastTransformersUtils
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
                file_get_contents(Library::FastTransformersUtils->header(basePath('includes'))),
                Library::FastTransformersUtils->library(basePath('libs'))
            );
        }

        return self::$ffi;
    }

    /**
     * Creates a new instance of the specified type.
     *
     * @param CType|string $type The type of the instance to create.
     * @param bool $owned Whether the instance should be owned. Default is true.
     * @param bool $persistent Whether the instance should be persistent. Default is false.
     *
     * @return CData|null The created instance, or null if the creation failed.
     * @throws Exception
     */
    public static function new(CType|string $type, bool $owned = true, bool $persistent = false): ?CData
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
        self::ffi();
        return '1.0.0';
    }

    public static function padReflect($input, int $length, int $paddedLength): CData
    {
        $padded = self::new("float[$paddedLength]");
        self::ffi()->pad_reflect($input, $length, $padded, $paddedLength);

        return $padded;
    }

    public static function spectrogram(
        $waveform, int $waveformLength, int $spectrogramLength, int $hopLength, int $fftLength,
        $window, int $windowLength, int $d1, int $d1Max, float $power, bool $center, float $preemphasis,
        $melFilters, int $nMelFilters, $nFreqBins, float $melFloor, int $logMel, ?bool $removeDcOffset,
        bool $doPad, bool $transpose
    ): CData
    {
        $spectrogram = self::new("float[$spectrogramLength]");

        self::ffi()->spectrogram(
            $waveform, $waveformLength, $spectrogram, $spectrogramLength, $hopLength, $fftLength, $window,
            $windowLength, $d1, $d1Max, $power, $center, $preemphasis, $melFilters, $nMelFilters, $nFreqBins,
            $melFloor, $logMel, $removeDcOffset, $doPad, $transpose,
        );

        return $spectrogram;
    }
}