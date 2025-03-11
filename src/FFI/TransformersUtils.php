<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

use FFI\CData;

class TransformersUtils extends NativeLibrary
{
    /**
     * Get the header file name for this library
     * 
     * @return string The header file name
     */
    protected function getHeaderName(): string
    {
        return 'transformersphp';
    }

    /**
     * Get the library file name (without extension) for this library
     * 
     * @return string The library file name
     */
    protected function getLibraryName(): string
    {
        return 'libtransformersphp';
    }


    /**
     * Returns the version of the library as a string.
     *
     * @return string The version of the library.
     */
    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * Pad an array using reflection
     * 
     * @param mixed $input The input array
     * @param int $length The length of the input array
     * @param int $paddedLength The length of the padded array
     * @return CData The padded array
     */
    public function padReflect($input, int $length, int $paddedLength): CData
    {
        $padded = $this->new("float[$paddedLength]");
        $this->ffi->{'pad_reflect'}($input, $length, $padded, $paddedLength);

        return $padded;
    }

    /**
     * Generate a spectrogram from a waveform
     * 
     * @param mixed $waveform The input waveform
     * @param int $waveformLength The length of the waveform
     * @param int $spectrogramLength The length of the spectrogram
     * @param int $hopLength The hop length
     * @param int $fftLength The FFT length
     * @param mixed $window The window function
     * @param int $windowLength The window length
     * @param int $d1 The first dimension
     * @param int $d1Max The maximum first dimension
     * @param float $power The power
     * @param bool $center Whether to center the window
     * @param float $preemphasis The preemphasis
     * @param mixed $melFilters The mel filters
     * @param int $nMelFilters The number of mel filters
     * @param mixed $nFreqBins The number of frequency bins
     * @param float $melFloor The mel floor
     * @param int $logMel Whether to use log mel
     * @param bool|null $removeDcOffset Whether to remove DC offset
     * @param bool $doPad Whether to pad
     * @param bool $transpose Whether to transpose
     * @return CData The spectrogram
     */
    public function spectrogram(
        $waveform, int $waveformLength, int $spectrogramLength, int $hopLength, int $fftLength,
        $window, int $windowLength, int $d1, int $d1Max, float $power, bool $center, float $preemphasis,
        $melFilters, int $nMelFilters, $nFreqBins, float $melFloor, int $logMel, ?bool $removeDcOffset,
        bool $doPad, bool $transpose
    ): CData
    {
        $spectrogram = $this->new("float[$spectrogramLength]");

        $this->ffi->{'spectrogram'}(
            $waveform, $waveformLength, $spectrogram, $spectrogramLength, $hopLength, $fftLength, $window,
            $windowLength, $d1, $d1Max, $power, $center, $preemphasis, $melFilters, $nMelFilters, $nFreqBins,
            $melFloor, $logMel, $removeDcOffset, $doPad, $transpose,
        );

        return $spectrogram;
    }
}