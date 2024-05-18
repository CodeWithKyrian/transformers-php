<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\DataStructures;

use Exception;
use SplFixedArray;

class NP2FFT
{
    public int $bufferSize;
    private int $a;
    private $chirpBuffer;
    private $buffer1;
    private $buffer2;
    private $outBuffer1;
    private $outBuffer2;
    private $slicedChirpBuffer;
    private $f;

    /**
     * Constructs a new NP2FFT object.
     * @param int $fftLength The length of the FFT
     * @throws Exception
     */
    public function __construct(int $fftLength)
    {
        // Helper variables
        $a = 2 * ($fftLength - 1);
        $b = 2 * (2 * $fftLength - 1);
        $nextP2 = pow(2, ceil(log($b, 2)));
        $this->bufferSize = $nextP2;
        $this->a = $a;

        // Define buffers
        // Compute chirp for transform
        $chirp = new SplFixedArray($b);
        $ichirp = new SplFixedArray($nextP2);
        $this->chirpBuffer = new SplFixedArray($nextP2);
        $this->buffer1 = new SplFixedArray($nextP2);
        $this->buffer2 = new SplFixedArray($nextP2);
        $this->outBuffer1 = new SplFixedArray($nextP2);
        $this->outBuffer2 = new SplFixedArray($nextP2);

        // Compute complex exponentiation
        $theta = -2 * M_PI / $fftLength;
        $baseR = cos($theta);
        $baseI = sin($theta);

        // Precompute helper for chirp-z transform
        for ($i = 0; $i < $b >> 1; ++$i) {
            // Compute complex power:
            $e = pow(($i + 1 - $fftLength), 2) / 2.0;

            // Compute the modulus and argument of the result
            $resultMod = pow(sqrt(pow($baseR, 2) + pow($baseI, 2)), $e);
            $resultArg = $e * atan2($baseI, $baseR);

            // Convert the result back to rectangular form
            // and assign to chirp and ichirp
            $i2 = 2 * $i;
            $chirp[$i2] = $resultMod * cos($resultArg);
            $chirp[$i2 + 1] = $resultMod * sin($resultArg);

            // conjugate
            $ichirp[$i2] = $chirp[$i2];
            $ichirp[$i2 + 1] = -$chirp[$i2 + 1];
        }
        $this->slicedChirpBuffer = SplFixedArray::fromArray(array_slice($chirp->toArray(), $a, $b - $a));

        // create object to perform Fast Fourier Transforms
        // with `nextP2` complex numbers
        $this->f = new P2FFT($nextP2 >> 1);
        $this->f->transform($this->chirpBuffer, $ichirp);
    }

    private function transform(SplFixedArray $output, SplFixedArray $input, bool $real = false): void
    {
        $ib1 = $this->buffer1;
        $ib2 = $this->buffer2;
        $ob2 = $this->outBuffer1;
        $ob3 = $this->outBuffer2;
        $cb = $this->chirpBuffer;
        $sb = $this->slicedChirpBuffer;
        $a = $this->a;

        if ($real) {
            // Real multiplication
            for ($j = 0; $j < count($sb); $j += 2) {
                $j2 = $j + 1;
                $j3 = $j >> 1;

                $aReal = $input[$j3];
                $ib1[$j] = $aReal * $sb[$j];
                $ib1[$j2] = $aReal * $sb[$j2];
            }
        } else {
            // Complex multiplication
            for ($j = 0; $j < count($sb); $j += 2) {
                $j2 = $j + 1;
                $ib1[$j] = $input[$j] * $sb[$j] - $input[$j2] * $sb[$j2];
                $ib1[$j2] = $input[$j] * $sb[$j2] + $input[$j2] * $sb[$j];
            }
        }
        $this->f->transform($ob2, $ib1);

        for ($j = 0; $j < count($cb); $j += 2) {
            $j2 = $j + 1;

            $ib2[$j] = $ob2[$j] * $cb[$j] - $ob2[$j2] * $cb[$j2];
            $ib2[$j2] = $ob2[$j] * $cb[$j2] + $ob2[$j2] * $cb[$j];
        }
        $this->f->inverseTransform($ob3, $ib2);

        for ($j = 0; $j < count($ob3); $j += 2) {
            $aReal = $ob3[$j + $a];
            $a_imag = $ob3[$j + $a + 1];
            $b_real = $sb[$j];
            $b_imag = $sb[$j + 1];

            $output[$j] = $aReal * $b_real - $a_imag * $b_imag;
            $output[$j + 1] = $aReal * $b_imag + $a_imag * $b_real;
        }
    }

    public function realTransform(SplFixedArray $output, SplFixedArray $input): void
    {
        $this->transform($output, $input, true);
    }
}