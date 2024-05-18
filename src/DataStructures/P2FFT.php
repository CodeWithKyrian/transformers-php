<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\DataStructures;

use Exception;
use SplFixedArray;

class P2FFT
{
    /**
     * @var int Size of the input array
     */
    private int $size;

    /**
     * @var int Size of the complex array (2 * size)
     */
    private int $csize;

    /**
     * @var SplFixedArray<int> Bit-reversal patterns
     */
    private SplFixedArray $bitRev;

    /**
     * @var int Initial step width
     */
    private int $width;

    /**
     * @var SplFixedArray<float> The trigonometric table
     */
    private SplFixedArray $table;

    /**
     * Constructor for the P2FFT class.
     *
     * @param int $size The size of the input array. Must be a power of two larger than 1.
     * @throws Exception FFT size must be a power of two larger than 1.
     */
    public function __construct(int $size)
    {
        $this->size = $size;

        if ($this->size <= 1 || !FFT::isPowerOfTwo($this->size)) {
            throw new Exception('FFT size must be a power of two larger than 1');
        }

        $this->csize = $this->size << 1;

        $this->table = new SplFixedArray($size * 2);

        for ($i = 0; $i < $this->size * 2; $i += 2) {
            $angle = M_PI * $i / $this->size;
            $this->table[$i] = cos($angle);
            $this->table[$i + 1] = -sin($angle);
        }

        $power = 0;
        for ($t = 1; $this->size > $t; $t <<= 1) {
            ++$power;
        }

        $this->width = $power % 2 === 0 ? $power - 1 : $power;

        $this->bitRev = new SplFixedArray(1 << $this->width);

        for ($j = 0; $j < 1 << $this->width; ++$j) {
            $this->bitRev[$j] = 0;
            for ($shift = 0; $shift < $this->width; $shift += 2) {
                $revShift = $this->width - $shift - 2;
//                $this->bitRev[$j] |= (($j >>> $shift) & 3) << $revShift;
                // PHP does not have a bitwise shift operator for negative numbers
                // so we need to use a different approach

                $shifted = $shift < 0 ? $j << abs($shift) : $j >> $shift;

                if ($revShift < 0) {
                    $this->bitRev[$j] |= ($shifted & 3) >> abs($revShift);
                } else {
                    $this->bitRev[$j] |= ($shifted & 3) << $revShift;
                }
            }
        }
    }

    /**
     * Create a complex number array with size `2 * size`
     *
     * @return SplFixedArray A complex number array with size `2 * size`
     */
    public function createComplexArray(): SplFixedArray
    {
        return new SplFixedArray($this->csize);
    }

    /**
     * Converts a complex number representation stored in a Float64Array to an array of real numbers.
     *
     * @param SplFixedArray $complex The complex number representation to be converted.
     * @param ?SplFixedArray $storage An optional array to store the result in.
     * @return SplFixedArray An array of real numbers representing the input complex number representation.
     */
    public function fromComplexArray(SplFixedArray $complex, ?SplFixedArray $storage = null): SplFixedArray
    {
        $res = $storage ?? new SplFixedArray($complex->count() >> 1);
        for ($i = 0; $i < count($complex); $i += 2) {
            $res[$i >> 1] = $complex[$i];
        }
        return $res;
    }

    /**
     * Convert a real-valued input array to a complex-valued output array.
     * @param SplFixedArray $input The real-valued input array.
     * @param ?SplFixedArray $storage Optional buffer to store the output array.
     * @return SplFixedArray The complex-valued output array.
     */
    public function toComplexArray(SplFixedArray $input, ?SplFixedArray $storage = null): SplFixedArray
    {
        $res = $storage ?? $this->createComplexArray();
        for ($i = 0; $i < count($res); $i += 2) {
            $res[$i] = $input[$i >> 1];
            $res[$i + 1] = 0.0;
        }
        return $res;
    }

    /**
     * Completes the spectrum by adding its mirrored negative frequency components.
     * @param SplFixedArray $spectrum The input spectrum.
     * @return void
     */
    public function completeSpectrum(SplFixedArray $spectrum): void
    {
        $size = $this->csize;
        $half = $size >> 1;
        for ($i = 2; $i < $half; $i += 2) {
            $spectrum[$size - $i] = $spectrum[$i];
            $spectrum[$size - $i + 1] = -$spectrum[$i + 1];
        }
    }

    /**
     * Performs a Fast Fourier Transform (FFT) on the given input data and stores the result in the output buffer.
     *
     * @param SplFixedArray $out The output buffer to store the result.
     * @param SplFixedArray $data The input data to transform.
     *
     * @return void
     * @throws Exception Input and output buffers must be different.
     *
     */
    public function transform(SplFixedArray $out, SplFixedArray $data): void
    {
        if ($out === $data) {
            throw new Exception('Input and output buffers must be different');
        }

        $this->transform4($out, $data, 1 /* DONE */);
    }

    /**
     * Performs a real-valued forward FFT on the given input buffer and stores the result in the given output buffer.
     * The input buffer must contain real values only, while the output buffer will contain complex values. The input and
     * output buffers must be different.
     *
     * @param SplFixedArray $out The output buffer for the transformed data.
     * @param SplFixedArray $data The input data to transform.
     *
     * @return void
     * @throws Exception If `out` and `data` refer to the same buffer.
     */
    public function realTransform(SplFixedArray $out, SplFixedArray $data): void
    {
        if ($out === $data) {
            throw new Exception('Input and output buffers must be different');
        }

        $this->realTransform4($out, $data, 1 /* DONE */);
    }

    /**
     * Performs an inverse FFT transformation on the given `data` array, and stores the result in `out`.
     * The `out` array must be a different buffer than the `data` array. The `out` array will contain the
     * result of the transformation. The `data` array will not be modified.
     *
     * @param SplFixedArray $out The output buffer for the transformed data.
     * @param SplFixedArray $data The input data to transform.
     *
     * @return void
     * @throws Exception If `out` and `data` refer to the same buffer.
     */
    public function inverseTransform(SplFixedArray $out, SplFixedArray $data): void
    {
        if ($out === $data) {
            throw new Exception('Input and output buffers must be different');
        }

        $this->transform4($out, $data, -1 /* DONE */);
        for ($i = 0; $i < count($out); ++$i) {
            $out[$i] /= $this->size;
        }
    }

    /**
     * Performs a radix-4 implementation of a discrete Fourier transform on a given set of data.
     *
     * @param SplFixedArray $out The output buffer for the transformed data.
     * @param SplFixedArray $data The input buffer of data to be transformed.
     * @param int $inv A scaling factor to apply to the transform.
     * @return void
     */
    private function transform4(SplFixedArray $out, SplFixedArray $data, int $inv): void
    {
        // radix-4 implementation

        $size = $this->csize;

        // Initial step (permute and transform)
        $width = $this->width;
        $step = 1 << $width;
        $len = ($size / $step) << 1;

        if ($len === 4) {
            for ($outOff = 0, $t = 0; $outOff < $size; $outOff += $len, ++$t) {
                $off = $this->bitRev[$t];
                $this->singleTransform2($data, $out, $outOff, $off, $step);
            }
        } else {
            // len === 8
            for ($outOff = 0, $t = 0; $outOff < $size; $outOff += $len, ++$t) {
                $off = $this->bitRev[$t];
                $this->singleTransform4($data, $out, $outOff, $off, $step, $inv);
            }
        }

        // Loop through steps in decreasing order
        for ($step >>= 2; $step >= 2; $step >>= 2) {
            $len = ($size / $step) << 1;
            $quarterLen = $len >> 2;

            // Loop through offsets in the data
            for ($outOff = 0; $outOff < $size; $outOff += $len) {
                // Full case
                $limit = $outOff + $quarterLen - 1;
                for ($i = $outOff, $k = 0; $i < $limit; $i += 2, $k += $step) {
                    $A = $i;
                    $B = $A + $quarterLen;
                    $C = $B + $quarterLen;
                    $D = $C + $quarterLen;

                    // Original values
                    $Ar = $out[$A];
                    $Ai = $out[$A + 1];
                    $Br = $out[$B];
                    $Bi = $out[$B + 1];
                    $Cr = $out[$C];
                    $Ci = $out[$C + 1];
                    $Dr = $out[$D];
                    $Di = $out[$D + 1];

                    $tableBr = $this->table[$k];
                    $tableBi = $inv * $this->table[$k + 1];
                    $MBr = $Br * $tableBr - $Bi * $tableBi;
                    $MBi = $Br * $tableBi + $Bi * $tableBr;

                    $tableCr = $this->table[2 * $k];
                    $tableCi = $inv * $this->table[2 * $k + 1];
                    $MCr = $Cr * $tableCr - $Ci * $tableCi;
                    $MCi = $Cr * $tableCi + $Ci * $tableCr;

                    $tableDr = $this->table[3 * $k];
                    $tableDi = $inv * $this->table[3 * $k + 1];
                    $MDr = $Dr * $tableDr - $Di * $tableDi;
                    $MDi = $Dr * $tableDi + $Di * $tableDr;

                    // Pre-Final values
                    $T0r = $Ar + $MCr;
                    $T0i = $Ai + $MCi;
                    $T1r = $Ar - $MCr;
                    $T1i = $Ai - $MCi;
                    $T2r = $MBr + $MDr;
                    $T2i = $MBi + $MDi;
                    $T3r = $inv * ($MBr - $MDr);
                    $T3i = $inv * ($MBi - $MDi);

                    // Final values
                    $out[$A] = $T0r + $T2r;
                    $out[$A + 1] = $T0i + $T2i;
                    $out[$B] = $T1r + $T3i;
                    $out[$B + 1] = $T1i - $T3r;
                    $out[$C] = $T0r - $T2r;
                    $out[$C + 1] = $T0i - $T2i;
                    $out[$D] = $T1r - $T3i;
                    $out[$D + 1] = $T1i + $T3r;
                }
            }
        }
    }

    /**
     * Performs a radix-2 implementation of a discrete Fourier transform on a given set of data.
     *
     * @param SplFixedArray $data The input buffer of data to be transformed.
     * @param SplFixedArray $out The output buffer for the transformed data.
     * @param int $outOff The offset at which to write the output data.
     * @param int $off The offset at which to begin reading the input data.
     * @param int $step The step size for indexing the input data.
     * @return void
     */
    private function singleTransform2(SplFixedArray $data, SplFixedArray $out, int $outOff, int $off, int $step): void
    {
        // radix-2 implementation
        // NOTE: Only called for len=4
        $evenR = $data[$off];
        $evenI = $data[$off + 1];
        $oddR = $data[$off + $step];
        $oddI = $data[$off + $step + 1];

        $out[$outOff] = $evenR + $oddR;
        $out[$outOff + 1] = $evenI + $oddI;
        $out[$outOff + 2] = $evenR - $oddR;
        $out[$outOff + 3] = $evenI - $oddI;
    }

    /**
     * Performs radix-4 transformation on input data of length 8
     *
     * @param SplFixedArray $data Input data array of length 8
     * @param SplFixedArray $out Output data array of length 8
     * @param int $outOff Index of output array to start writing from
     * @param int $off Index of input array to start reading from
     * @param int $step Step size between elements in input array
     * @param int $inv Scaling factor for inverse transform
     * @return void
     */
    private function singleTransform4(SplFixedArray $data, SplFixedArray $out, int $outOff, int $off, int $step, int $inv): void
    {
        // radix-4
        // NOTE: Only called for len=8
        $step2 = $step * 2;
        $step3 = $step * 3;

        // Original values
        $Ar = $data[$off];
        $Ai = $data[$off + 1];
        $Br = $data[$off + $step];
        $Bi = $data[$off + $step + 1];
        $Cr = $data[$off + $step2];
        $Ci = $data[$off + $step2 + 1];
        $Dr = $data[$off + $step3];
        $Di = $data[$off + $step3 + 1];

        // Pre-Final values
        $T0r = $Ar + $Cr;
        $T0i = $Ai + $Ci;
        $T1r = $Ar - $Cr;
        $T1i = $Ai - $Ci;
        $T2r = $Br + $Dr;
        $T2i = $Bi + $Di;
        $T3r = $inv * ($Br - $Dr);
        $T3i = $inv * ($Bi - $Di);

        // Final values
        $out[$outOff] = $T0r + $T2r;
        $out[$outOff + 1] = $T0i + $T2i;
        $out[$outOff + 2] = $T1r + $T3i;
        $out[$outOff + 3] = $T1i - $T3r;
        $out[$outOff + 4] = $T0r - $T2r;
        $out[$outOff + 5] = $T0i - $T2i;
        $out[$outOff + 6] = $T1r - $T3i;
        $out[$outOff + 7] = $T1i + $T3r;
    }

    /**
     * Real input radix-4 implementation
     *
     * @param SplFixedArray $out Output array for the transformed data
     * @param SplFixedArray $data Input array of real data to be transformed
     * @param int $inv The scale factor used to normalize the inverse transform
     * @return void
     */
    private function realTransform4(SplFixedArray $out, SplFixedArray $data, int $inv): void
    {
        // Real input radix-4 implementation
        $size = $this->csize;

        // Initial step (permute and transform)
        $width = $this->width;
        $step = 1 << $width;
        $len = ($size / $step) << 1;

        if ($len === 4) {
            for ($outOff = 0, $t = 0; $outOff < $size; $outOff += $len, ++$t) {
                $off = $this->bitRev[$t];

                $this->singleRealTransform2($data, $out, $outOff, $off >> 1, $step >> 1);
            }
        } else {
            // len === 8
            for ($outOff = 0, $t = 0; $outOff < $size; $outOff += $len, ++$t) {
                $off = $this->bitRev[$t];
                $this->singleRealTransform4($data, $out, $outOff, $off >> 1, $step >> 1, $inv);
            }
        }

        // Loop through steps in decreasing order
        for ($step >>= 2; $step >= 2; $step >>= 2) {
            $len = ($size / $step) << 1;
            $quarterLen = $len >> 2;

            // Loop through offsets in the data
            for ($outOff = 0; $outOff < $size; $outOff += $len) {
                // Full case
                $limit = $outOff + $quarterLen - 1;
                for ($i = $outOff, $k = 0; $i < $limit; $i += 2, $k += $step) {
                    $A = $i;
                    $B = $A + $quarterLen;
                    $C = $B + $quarterLen;
                    $D = $C + $quarterLen;

                    // Original values
                    $Ar = $out[$A];
                    $Ai = $out[$A + 1];
                    $Br = $out[$B];
                    $Bi = $out[$B + 1];
                    $Cr = $out[$C];
                    $Ci = $out[$C + 1];
                    $Dr = $out[$D];
                    $Di = $out[$D + 1];

                    $tableBr = $this->table[$k];
                    $tableBi = $inv * $this->table[$k + 1];
                    $MBr = $Br * $tableBr - $Bi * $tableBi;
                    $MBi = $Br * $tableBi + $Bi * $tableBr;

                    $tableCr = $this->table[2 * $k];
                    $tableCi = $inv * $this->table[2 * $k + 1];
                    $MCr = $Cr * $tableCr - $Ci * $tableCi;
                    $MCi = $Cr * $tableCi + $Ci * $tableCr;

                    $tableDr = $this->table[3 * $k];
                    $tableDi = $inv * $this->table[3 * $k + 1];
                    $MDr = $Dr * $tableDr - $Di * $tableDi;
                    $MDi = $Dr * $tableDi + $Di * $tableDr;

                    // Pre-Final values
                    $T0r = $Ar + $MCr;
                    $T0i = $Ai + $MCi;
                    $T1r = $Ar - $MCr;
                    $T1i = $Ai - $MCi;
                    $T2r = $MBr + $MDr;
                    $T2i = $MBi + $MDi;
                    $T3r = $inv * ($MBr - $MDr);
                    $T3i = $inv * ($MBi - $MDi);

                    // Final values
                    $out[$A] = $T0r + $T2r;
                    $out[$A + 1] = $T0i + $T2i;
                    $out[$B] = $T1r + $T3i;
                    $out[$B + 1] = $T1i - $T3r;
                    $out[$C] = $T0r - $T2r;
                    $out[$C + 1] = $T0i - $T2i;
                    $out[$D] = $T1r - $T3i;
                    $out[$D + 1] = $T1i + $T3r;
                }
            }
        }
    }

    /**
     * Performs a single real input radix-2 transformation on the provided data.
     *
     * @param SplFixedArray $data The input data array
     * @param SplFixedArray $out The output data array
     * @param int $outOff The output offset
     * @param int $off The input offset
     * @param int $step The step size for the input array
     * @return void
     */
    private function singleRealTransform2(SplFixedArray $data, SplFixedArray $out, int $outOff, int $off, int $step): void
    {
        // radix-2 implementation
        // NOTE: Only called for len=4

        $evenR = $data[$off];
        $oddR = $data[$off + $step];

        $out[$outOff] = $evenR + $oddR;
        $out[$outOff + 1] = 0;
        $out[$outOff + 2] = $evenR - $oddR;
        $out[$outOff + 3] = 0;
    }

    /**
     * Computes a single real-valued transform using radix-4 algorithm.
     * This method is only called for len=8.
     *
     * @param SplFixedArray $data The input data array
     * @param SplFixedArray $out The output data array
     * @param int $outOff The offset into the output array
     * @param int $off The offset into the input array
     * @param int $step The step size for the input array
     * @param int $inv The value of inverse
     * @return void
     */
    private function singleRealTransform4(SplFixedArray $data, SplFixedArray $out, int $outOff, int $off, int $step, int $inv): void
    {
        // radix-4
        // NOTE: Only called for len=8
        $step2 = $step * 2;
        $step3 = $step * 3;

        // Original values
        $Ar = $data[$off];
        $Br = $data[$off + $step];
        $Cr = $data[$off + $step2];
        $Dr = $data[$off + $step3];

        // Pre-Final values
        $T0r = $Ar + $Cr;
        $T1r = $Ar - $Cr;
        $T2r = $Br + $Dr;
        $T3r = $inv * ($Br - $Dr);

        // Final values
        $out[$outOff] = $T0r + $T2r;
        $out[$outOff + 1] = 0;
        $out[$outOff + 2] = $T1r;
        $out[$outOff + 3] = -$T3r;
        $out[$outOff + 4] = $T0r - $T2r;
        $out[$outOff + 5] = 0;
        $out[$outOff + 6] = $T1r;
        $out[$outOff + 7] = $T3r;
    }
}