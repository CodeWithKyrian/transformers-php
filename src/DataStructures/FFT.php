<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\DataStructures;

use SplFixedArray;

class FFT
{
    private bool $isPowerOfTwo;
    private NP2FFT|P2FFT $fft;
    public int $outputBufferSize;

    public function __construct(protected int $fftLength)
    {
        $this->isPowerOfTwo = self::isPowerOfTwo($fftLength);

        if ($this->isPowerOfTwo) {
            $this->fft = new P2FFT($fftLength);
            $this->outputBufferSize = 2 * $fftLength;
        } else {
            $this->fft = new NP2FFT($fftLength);
            $this->outputBufferSize = $this->fft->bufferSize;
        }
    }

    public function realTransform(SplFixedArray $out, SplFixedArray $input): void
    {
        $this->fft->realTransform($out, $input);
    }

    public function transform(SplFixedArray $out, SplFixedArray $input): void
    {
        $this->fft->transform($out, $input);
    }

    public static function isPowerOfTwo($n): bool
    {
        return ($n > 0) && (($n & ($n - 1)) == 0);
    }
}