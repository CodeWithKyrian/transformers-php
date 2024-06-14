<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FFI;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\TransformersLibrariesDownloader\Libraries;
use FFI;
use FFI\CData;

class FastTransformersUtils
{
    private static FFI $ffi;

    private static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            $headerCode = file_get_contents(Libraries::FastTransformersUtils->headerFile(Transformers::$libsDir));
            self::$ffi = FFI::cdef($headerCode, Libraries::FastTransformersUtils->libFile(Transformers::$libsDir));
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

    public static function padReflect($input, int $length, int $paddedLength): CData
    {
        $padded = FFI::new("float[$paddedLength]");
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
        $spectrogram = FFI::new("float[$spectrogramLength]");

        self::ffi()->spectrogram(
            $waveform, $waveformLength, $spectrogram, $spectrogramLength, $hopLength, $fftLength, $window,
            $windowLength, $d1, $d1Max, $power, $center, $preemphasis, $melFilters, $nMelFilters, $nFreqBins,
            $melFloor, $logMel, $removeDcOffset, $doPad, $transpose,
        );

        return $spectrogram;
    }
}