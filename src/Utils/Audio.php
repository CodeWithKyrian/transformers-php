<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\DataStructures\FFT;
use Codewithkyrian\Transformers\OnnxRuntime\FFI;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Transformers;
use InvalidArgumentException;
use RuntimeException;
use SplFixedArray;

class Audio
{
    private static \FFI $sndfileFFI;
    private static \FFI $samplerateFFI;

    public function __construct(protected $sndfile, protected $sfinfo)
    {
    }

    public static function sndfileFFI(): \FFI
    {
        if (!isset(self::$sndfileFFI)) {
            $headerFile = Transformers::$libsDir . '/libsndfile-darwin-1.2.2/include/sndfile.h';
            $libFile = Transformers::$libsDir . '/libsndfile-darwin-1.2.2/lib/libsndfile.dylib';
            self::$sndfileFFI = \FFI::cdef(file_get_contents($headerFile), $libFile);
        }

        return self::$sndfileFFI;
    }

    public static function sampleRateFFI(): \FFI
    {
        if (!isset(self::$samplerateFFI)) {
            $headerFile = Transformers::$libsDir . '/libsamplerate-darwin-0.2.2/include/samplerate.h';
            $libFile = Transformers::$libsDir . '/libsamplerate-darwin-0.2.2/lib/libsamplerate.dylib';
            self::$samplerateFFI = \FFI::cdef(file_get_contents($headerFile), $libFile);
        }

        return self::$samplerateFFI;
    }

    public static function read(string $filename): static
    {
        $sndfileFFI = self::sndfileFFI();

        $sfinfo = $sndfileFFI->new('SF_INFO');

        if (PHP_OS_FAMILY === 'Windows') {
            $sndfile = $sndfileFFI->sf_wchar_open($filename, $sndfileFFI->SFM_READ, \FFI::addr($sfinfo));
        } else {
            $sndfile = $sndfileFFI->sf_open($filename, $sndfileFFI->SFM_READ, \FFI::addr($sfinfo));
        }

        if ($sndfile === null) {
            $error = $sndfileFFI->sf_strerror($sndfile);
            throw new RuntimeException("Failed to open file: $error");
        }

        return new static($sndfile, $sfinfo);
    }

    public function format(): string
    {
        $ffi = self::sndfileFFI();
        $info = $ffi->new('SF_FORMAT_INFO');
        $info->format = $this->sfinfo->format;
        $ffi->sf_command($this->sndfile, $ffi->SFC_GET_FORMAT_INFO, \FFI::addr($info), \FFI::sizeof($info));

        return $info->name;
    }

    public function channels(): int
    {
        return $this->sfinfo->channels;
    }

    public function samplerate(): int
    {
        return $this->sfinfo->samplerate;
    }

    public function frames(): int
    {
        return $this->sfinfo->frames;
    }

    public function duration(): float
    {
        return round($this->sfinfo->frames / $this->sfinfo->samplerate, 2);
    }

    public function toTensor(int $channels = 1, int $samplerate = 41000, int $chunkSize = 2048): Tensor
    {
        $sndfileFFI = self::sndfileFFI();
        $sampleRateFFI = self::sampleRateFFI();

        $tensorData = '';
        $totalOutputFrames = 0;

        $error = $sampleRateFFI->new('int32_t');
        $state = $sampleRateFFI->src_new($sampleRateFFI->SRC_SINC_FASTEST, $channels, \FFI::addr($error));

        if ($error->cdata !== 0) {
            $error = $sampleRateFFI->src_strerror($error);
            throw new RuntimeException("Failed to create sample rate converter: $error");
        }

        $inputSize = $chunkSize * $channels;
        $inputData = $sndfileFFI->new("float[$inputSize]");
        $outputSize = $chunkSize / $channels;
        $outputData = $sndfileFFI->new("float[$outputSize]");

        $srcData = $sampleRateFFI->new('SRC_DATA');
        $srcData->data_in = \FFI::addr($inputData[0]);
        $srcData->output_frames = $chunkSize / $channels;
        $srcData->data_out = \FFI::addr($outputData[0]);
        $srcData->src_ratio = $samplerate / $this->samplerate();

        while (true) {
            /* Read the chunk of data */
            $srcData->input_frames = $sndfileFFI->sf_readf_float($this->sndfile, $inputData, $chunkSize);

            /* Add to tensor data without resample if the sample rate is the same */
            if ($this->samplerate() === $samplerate) {
                $strBuffer = \FFI::string($inputData, $srcData->input_frames * $channels * \FFI::sizeof($inputData[0]));
                $tensorData .= $strBuffer;
                $totalOutputFrames += $srcData->input_frames;
                if ($srcData->input_frames < $chunkSize) {
                    break;
                }
                continue;
            }

            /* The last read will not be a full buffer, so snd_of_input. */
            if ($srcData->input_frames < $chunkSize) {
                $srcData->end_of_input = $sndfileFFI->SF_TRUE;
            }

            /* Process current block. */
            $error = $sampleRateFFI->src_process($state, \FFI::addr($srcData));
            if ($error !== 0) {
                $error = $sampleRateFFI->src_strerror($error);
                throw new RuntimeException("Failed to convert sample rate: $error");
            }

            /* Terminate if done. */
            if ($srcData->end_of_input && $srcData->output_frames_gen === 0) {
                break;
            }

            /* Add the processed data to the tensor data */
            $outputSize = $srcData->output_frames_gen * $channels * \FFI::sizeof($outputData[0]);
            $strBuffer = \FFI::string($outputData, $outputSize);
            $tensorData .= $strBuffer;
            $totalOutputFrames += $srcData->output_frames_gen;
        }

//        \FFI::free($srcData->data_in);
//        \FFI::free($srcData->data_out);

        $sampleRateFFI->src_delete($state);

        return Tensor::fromString($tensorData, Tensor::float32, [$channels, $totalOutputFrames]);
    }

    public function fromTensor(Tensor $tensor): void
    {
        $ffi = self::sndfileFFI();
        $size = $tensor->size();
        $buffer = $ffi->new("float[$size]");
        $bufferString = $tensor->toString();
        $buffer->cdata = \FFI::cast('float *', $bufferString);

        $write = $ffi->sf_writef_float($this->sndfile, $buffer, $size);

        if ($write !== $size) {
            throw new RuntimeException("Failed to write to file");
        }
    }


    public function __destruct()
    {
        self::sndfileFFI()->sf_close($this->sndfile);
    }

    /**
     * Creates a triangular filter bank.
     *
     * Adapted from torchaudio and librosa.
     *
     * @param int $nFrequencyBins Number of frequencies used to compute the spectrogram (should be the same as in `stft`).
     * @param int $nMelFilters Number of mel filters to generate.
     * @param float $minFrequency Lowest frequency of interest in Hz.
     * @param float $maxFrequency Highest frequency of interest in Hz. This should not exceed `sampling_rate / 2`.
     * @param float $samplingRate Sample rate of the audio waveform.
     * @param string|null $norm If `"slaney"`, divide the triangular mel weights by the width of the mel band (area normalization).
     * @param string $melScale The mel frequency scale to use, `"htk"` or `"slaney"`.
     * @param bool $triangularizeInMelSpace If this option is enabled, the triangular filter is applied in mel space rather than frequency space.
     * @return array Triangular filter bank matrix, which is a 2D array of shape (`num_frequency_bins`, `num_mel_filters`).
     * This is a projection matrix to go from a spectrogram to a mel spectrogram.
     */
    public static function melFilterBank(
        int     $nFrequencyBins,
        int     $nMelFilters,
        float   $minFrequency,
        float   $maxFrequency,
        float   $samplingRate,
        ?string $norm = null,
        string  $melScale = "htk",
        bool    $triangularizeInMelSpace = false
    ): array
    {
        if ($norm !== null && $norm !== "slaney") {
            throw new InvalidArgumentException('norm must be one of null or "slaney"');
        }

        $melMin = self::hertzToMel($minFrequency, $melScale);
        $melMax = self::hertzToMel($maxFrequency, $melScale);
        $melFreqs = self::linspace($melMin, $melMax, $nMelFilters + 2);

        $filterFreqs = self::melToHertz($melFreqs, $melScale);
        $fftFreqs = []; // frequencies of FFT bins in Hz

        if ($triangularizeInMelSpace) {
            $fft_bin_width = $samplingRate / ($nFrequencyBins * 2);
            $fftFreqs = self::hertzToMel(array_map(fn($i) => $i * $fft_bin_width, range(0, $nFrequencyBins - 1)), $melScale);
            $filterFreqs = $melFreqs;
        } else {
            $fftFreqs = self::linspace(0, floor($samplingRate / 2), $nFrequencyBins);
        }

        $melFilters = self::createTriangularFilterBank($fftFreqs, $filterFreqs);

        if ($norm === "slaney") {
            // Slaney-style mel is scaled to be approx constant energy per channel
            for ($i = 0; $i < $nMelFilters; ++$i) {
                $enorm = 2.0 / ($filterFreqs[$i + 2] - $filterFreqs[$i]);
                for ($j = 0; $j < $nFrequencyBins; ++$j) {
                    $melFilters[$j][$i] *= $enorm;
                }
            }
        }

        // TODO warn if there is a zero row

        return $melFilters;
    }

    /**
     * Creates a frequency bin conversion matrix used to obtain a mel spectrogram. This is called a *mel filter bank*, and
     * various implementation exist, which differ in the number of filters, the shape of the filters, the way the filters
     * are spaced, the bandwidth of the filters, and the manner in which the spectrum is warped. The goal of these
     * features is to approximate the non-linear human perception of the variation in pitch with respect to the frequency.
     * @param float[] $fftFreqs Discrete frequencies of the FFT bins in Hz, of shape `(num_frequency_bins,)`.
     * @param float[] $filterFreqs Center frequencies of the triangular filters to create, in Hz, of shape `(num_mel_filters,)`.
     * @return array of shape `(num_frequency_bins, num_mel_filters)`.
     */
    private static function createTriangularFilterBank(array $fftFreqs, array $filterFreqs): array
    {
        $filterDiff = [];
        for ($i = 0; $i < count($filterFreqs) - 1; $i++) {
            $filterDiff[$i] = $filterFreqs[$i + 1] - $filterFreqs[$i];
        }


        $slopes = [];
        foreach ($fftFreqs as $freq) {
            $slope = [];
            foreach ($filterFreqs as $filterFreq) {
                $slope[] = $filterFreq - $freq;
            }
            $slopes[] = $slope;
        }


        $numFreqs = count($filterFreqs) - 2;

        $ret = [];

        foreach ($fftFreqs as $j => $fft_freq) {
            $slope = $slopes[$j];
            for ($i = 0; $i < $numFreqs; $i++) {
                $down = -$slope[$i] / $filterDiff[$i];
                $up = $slope[$i + 2] / $filterDiff[$i + 1];
                $ret[$i][$j] = max(0, min($down, $up));
            }
        }

        return $ret;
    }

    /**
     * Return evenly spaced numbers over a specified interval.
     * @param float $start The starting value of the sequence.
     * @param float $end The end value of the sequence.
     * @param int $num Number of samples to generate.
     * @return float[] `num` evenly spaced samples, calculated over the interval `[start, stop]`.
     */
    private static function linspace(float $start, float $end, int $num): array
    {
        $step = ($end - $start) / ($num - 1);
        return array_map(fn($i) => $start + $step * $i, range(0, $num - 1));
    }

    public static function hertzToMel(array|float|int $hz, string $melScale = "htk"): float|int|array
    {
        if (is_array($hz)) {
            return array_map(fn($i) => self::hertzToMel($i, $melScale), $hz);
        }

        if ($melScale === "htk") {
            return 2595.0 * log10(1.0 + $hz / 700.0);
        }

        if ($melScale === "kaldi") {
            return 1127.0 * log(1.0 + $hz / 700.0);
        }

        if ($melScale === "slaney") {
            $minLogHz = 1000.0;
            $minLogMel = 15.0;
            $logStep = 27.0 / log(6.4);

            return $hz >= $minLogHz ? $minLogMel + log($hz / $minLogHz) * $logStep : 3.0 * $hz / 200.0;
        }

        throw new InvalidArgumentException('mel_scale must be one of "htk", "kaldi", or "slaney"');
    }

    public static function melToHertz(array|float|int $mel, string $melScale = "htk"): float|int|array
    {
        if (is_array($mel)) {
            return array_map(fn($i) => self::melToHertz($i, $melScale), $mel);
        }

        if ($melScale === "htk") {
            return 700.0 * (pow(10.0, $mel / 2595.0) - 1.0);
        }

        if ($melScale === "kaldi") {
            return 700.0 * (exp($mel / 1127.0) - 1.0);
        }

        if ($melScale === "slaney") {
            $minLogHz = 1000.0;
            $minLogMel = 15.0;
            $logStep = log(6.4) / 27.0;

            return $mel >= $minLogMel ? $minLogHz * exp($logStep * ($mel - $minLogMel)) : 200.0 * $mel / 3.0;
        }

        throw new InvalidArgumentException('mel_scale must be one of "htk", "kaldi", or "slaney"');
    }

    /**
     * Helper function to compute `amplitude_to_db` and `power_to_db`.
     */
    private static function dBConversionHelper(
        Tensor  $spectrogram,
        float  $factor,
        float  $reference,
        float  $minValue,
        ?float $dbRange
    ): Tensor
    {
        if ($reference <= 0) {
            throw new InvalidArgumentException('reference must be greater than zero');
        }

        if ($minValue <= 0) {
            throw new InvalidArgumentException('min_value must be greater than zero');
        }

        $reference = max($minValue, $reference);
        $logReference = log10($reference);

//        for ($i = 0; $i < count($spectrogram); $i++) {
//            $spectrogram->buffer()[$i] = $factor * log10(max($minValue, $spectrogram->buffer()[$i]) - $logReference);
//        }
        $spectrogram->u(fn($x) => $factor * log10(max($minValue, $x) - $logReference));

        if ($dbRange !== null) {
            if ($dbRange <= 0) {
                throw new InvalidArgumentException('db_range must be greater than zero');
            }

            $maxValue = $spectrogram->max() - $dbRange;

//            for ($i = 0; $i < count($spectrogram); $i++) {
//                $spectrogram->buffer()[$i] = max($spectrogram->buffer()[$i], $maxValue);
//            }
            $spectrogram->u(fn($x) => max($x, $maxValue));
        }

        return $spectrogram;
    }

    /**
     * Converts an amplitude spectrogram to the decibel scale. This computes `20 * log10(spectrogram / reference)`,
     *  using basic logarithm properties for numerical stability. NOTE: Operates in-place.
     *
     * @param SplFixedArray $spectrogram The input amplitude (mel) spectrogram.
     * @param float $reference Sets the input spectrogram value that corresponds to 0 dB.
     * @param float $minValue Minimum threshold for `spectrogram` and `reference` values.
     * @param float|null $dbRange Dynamic range of the resulting decibel scale. If set, the decibel scale is compressed
     * @return SplFixedArray
     */
    public static function amplitudeToDB(
        Tensor  $spectrogram,
        float  $reference = 1.0,
        float  $minValue = 1e-5,
        ?float $dbRange = null
    ): Tensor
    {
        return self::dBConversionHelper($spectrogram, 20.0, $reference, $minValue, $dbRange);
    }

    /**
     * Converts a power spectrogram (amplitude squared) to the decibel scale. This computes `10 * log10(spectrogram / reference)`,
     * using basic logarithm properties for numerical stability. NOTE: Operates in-place.
     *
     * @param SplFixedArray $spectrogram The input power spectrogram.
     * @param float $reference Sets the input spectrogram value that corresponds to 0 dB.
     * @param float $minValue Minimum threshold for `spectrogram` and `reference` values.
     * @param float|null $dbRange Dynamic range of the resulting decibel scale. If set, the decibel scale is compressed
     * @return SplFixedArray
     */
    public static function powerToDB(
        Tensor  $spectrogram,
        float  $reference = 1.0,
        float  $minValue = 1e-5,
        ?float $dbRange = null
    ): Tensor
    {
        return self::dBConversionHelper($spectrogram, 10.0, $reference, $minValue, $dbRange);
    }

    /**
     * Calculates a spectrogram over one waveform using the Short-Time Fourier Transform.
     *
     * This function can create the following kinds of spectrograms:
     *    - amplitude spectrogram (`power = 1.0`)
     *    - power spectrogram (`power = 2.0`)
     *    - complex-valued spectrogram (`power = None`)
     *    - log spectrogram (use `log_mel` argument)
     *    - mel spectrogram (provide `mel_filters`)
     *    - log-mel spectrogram (provide `mel_filters` and `log_mel`)
     *
     *  In this implementation, the window is assumed to be zero-padded to have the same size as the analysis frame.
     *  A padded window can be obtained from `windowFunction()`. The FFT input buffer may be larger than the analysis frame,
     *  typically the next power of two.
     */
    public static function spectrogram(
        Tensor        $waveform,
        SplFixedArray $window,
        int           $frameLength,
        int           $hopLength,
        ?int          $fftLength = null,
        float         $power = 1.0,
        bool          $center = true,
        string        $padMode = 'reflect',
        bool          $onesided = true,
        float         $preemphasis = null,
        ?array        $melFilters = null,
        float         $melFloor = 1e-10,
        ?string       $logMel = null,
        float         $reference = 1.0,
        float         $minValue = 1e-10,
        ?float        $dbRange = null,
        ?bool         $removeDcOffset = null,
        ?int          $maxNumFrames = null,
        bool          $doPad = true,
        bool          $transpose = false
    ): Tensor
    {
        $windowLength = count($window);

        if ($fftLength === null) {
            $fftLength = $frameLength;
        }

        if ($frameLength > $fftLength) {
            throw new InvalidArgumentException("frameLength ($frameLength) may not be larger than fftLength ($fftLength)");
        }

        if ($windowLength !== $frameLength) {
            throw new InvalidArgumentException("Length of the window ($windowLength) must equal frameLength ($frameLength)");
        }

        if ($hopLength <= 0) {
            throw new InvalidArgumentException("hopLength must be greater than zero");
        }

        if ($center) {
            if ($padMode !== 'reflect') {
                throw new InvalidArgumentException("pad_mode=\"{$padMode}\" not implemented yet.");
            }

            $halfWindow = floor(($fftLength - 1) / 2) + 1;
            $waveform = self::padReflect($waveform, $halfWindow, $halfWindow);
        }

        $numFrames = 1 + floor(($waveform->buffer()->count() - $frameLength) / $hopLength);
        $numFrequencyBins = $onesided ? floor($fftLength / 2) + 1 : $fftLength;

        $d1 = (int)$numFrames;
        $d1Max = (int)$numFrames;


        if ($maxNumFrames !== null) {
            if ($maxNumFrames > $numFrames) {
                if ($doPad) {
                    $d1Max = $maxNumFrames;
                }
            } else {
                $d1Max = $d1 = $maxNumFrames;
            }
        }
        $fft = new FFT($fftLength);
        $inputBuffer = new SplFixedArray($fftLength);
        $outputBuffer = new SplFixedArray($fft->outputBufferSize);
        $magnitudes = new SplFixedArray($d1);

        for ($i = 0; $i < $d1; ++$i) {
            $offset = $i * $hopLength;

            for ($j = 0; $j < $frameLength; ++$j) {
                $inputBuffer[$j] = $waveform->buffer()[$offset + $j];
            }

            if ($removeDcOffset) {
                $sum = 0;
                for ($j = 0; $j < $frameLength; ++$j) {
                    $sum += $inputBuffer[$j];
                }
                $mean = $sum / $frameLength;
                for ($j = 0; $j < $frameLength; ++$j) {
                    $inputBuffer[$j] -= $mean;
                }
            }

            if ($preemphasis !== null) {
                for ($j = $frameLength - 1; $j >= 1; --$j) {
                    $inputBuffer[$j] -= $preemphasis * $inputBuffer[$j - 1];
                }
                $inputBuffer[0] *= 1 - $preemphasis;
            }

            for ($j = 0; $j < $windowLength; ++$j) {
                $inputBuffer[$j] *= $window[$j];
            }

            $fft->realTransform($outputBuffer, $inputBuffer);

            $row = [];
            for ($j = 0; $j < $numFrequencyBins; ++$j) {
                $j2 = $j << 1;
                $row[$j] = $outputBuffer[$j2] ** 2 + $outputBuffer[$j2 + 1] ** 2;
            }
            $magnitudes[$i] = $row;

        }


        if ($power !== null && $power != 2) {
            $pow = 2 / $power;
            for ($i = 0; $i < $magnitudes->count(); $i++) {
                for ($j = 0; $j < count($magnitudes[$i]); $j++) {
                    $magnitudes[$i][$j] **= $pow;
                }
            }
        }

        $numMelFilters = count($melFilters);

        $shape = $transpose ? [$d1Max, $numMelFilters] : [$numMelFilters, $d1Max];

        $melSpec = new Tensor(null,  Tensor::float32, [array_product($shape)]);

        for ($i = 0; $i < $numMelFilters; ++$i) {
            $filter = $melFilters[$i];
            for ($j = 0; $j < $d1; ++$j) {
                $magnitude = $magnitudes[$j];

                $sum = 0;
                for ($k = 0; $k < $numFrequencyBins; ++$k) {
                    $sum += $filter[$k] * $magnitude[$k];
                }

                $melSpec->buffer()[$transpose ? $j * $numMelFilters + $i : $i * $d1 + $j] = max($melFloor, $sum);
            }

        }


        if ($power !== null && $logMel !== null) {
            $o = min($melSpec->count(), $d1 * $numMelFilters);

            switch ($logMel) {
                case 'log':
                    for ($i = 0; $i < $o; ++$i) {
                        $melSpec->buffer()[$i] = log($melSpec->buffer()[$i]);
                    }
                    break;
                case 'log10':
                    for ($i = 0; $i < $o; ++$i) {
                        $melSpec[$i] = log10($melSpec->buffer()[$i]);
                    }
                    break;
                case 'dB':
                    if ($power === 1.0) {
                        self::amplitudeToDB($melSpec, $reference, $minValue, $dbRange);
                    } elseif ($power === 2.0) {
                        self::powerToDB($melSpec, $reference, $minValue, $dbRange);
                    } else {
                        throw new InvalidArgumentException("Cannot use logMel option '$logMel' with power $power");
                    }
                    break;
                default:
                    throw new InvalidArgumentException("logMel must be one of null, 'log', 'log10' or 'dB'. Got '$logMel'");
            }
        }

        return $melSpec->reshape($shape);
    }

    /**
     * Generates a Hanning window of length M.
     *
     * @param int $M The length of the Hanning window to generate.
     * @return SplFixedArray The generated Hanning window.
     */
    private static function hanning(int $M): SplFixedArray
    {
        if ($M < 1) {
            return new SplFixedArray(0);
        }
        if ($M === 1) {
            $ret = new SplFixedArray(1);
            $ret[0] = 1.0;
            return $ret;
        }
        $denom = $M - 1;
        $factor = M_PI / $denom;
        $cosValues = new SplFixedArray($M);
        for ($i = 0; $i < $M; ++$i) {
            $n = 2 * $i - $denom;
            $cosValues[$i] = 0.5 + 0.5 * cos($factor * $n);
        }
        return $cosValues;
    }

    /**
     * Returns an array containing the specified window.
     * @param int $windowLength The length of the window in samples.
     * @param string $name The name of the window function.
     * @param bool $periodic Whether the window is periodic or symmetric.
     * @param int|null $frameLength The length of the analysis frames in samples.
     * Provide a value for `frame_length` if the window is smaller than the frame length, so that it will be zero-padded.
     * @param bool $center Whether to center the window inside the FFT buffer. Only used when `frameLength` is provided.
     * @return SplFixedArray The window of shape `(windowLength)` or `(frameLength)`.
     */
    public static function windowFunction(
        int    $windowLength,
        string $name,
        bool   $periodic = true,
        ?int   $frameLength = null,
        bool   $center = true
    ): SplFixedArray
    {

        $length = $periodic ? $windowLength + 1 : $windowLength;
        $window = null;

        switch ($name) {
            case 'boxcar':
                $window = new SplFixedArray($length);
                for ($i = 0; $i < $length; ++$i) {
                    $window[$i] = 1.0;
                }
                break;
            case 'hann':
            case 'hann_window':
                $window = self::hanning($length);
                break;
            case 'povey':
                $hanningWindow = self::hanning($length);
                $window = new SplFixedArray($length);
                for ($i = 0; $i < $length; ++$i) {
                    $window[$i] = pow($hanningWindow[$i], 0.85);
                }
                break;
            default:
                throw new InvalidArgumentException("Unknown window type $name.");
        }

        if ($periodic) {
            // TODO: Get subset of the array from 0 to windowLength
        }

        if ($frameLength === null) {
            return $window;
        }

        if ($windowLength > $frameLength) {
            throw new InvalidArgumentException("Length of the window ($windowLength) may not be larger than frame_length ($frameLength)");
        }

        return $window;
    }

}