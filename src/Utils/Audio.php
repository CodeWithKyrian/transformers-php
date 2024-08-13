<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\FFI\FastTransformersUtils;
use Codewithkyrian\Transformers\FFI\Samplerate;
use Codewithkyrian\Transformers\FFI\Sndfile;
use Codewithkyrian\Transformers\Tensor\Tensor;
use FFI;
use InvalidArgumentException;
use RuntimeException;
use SplFixedArray;

class Audio
{
    public function __construct(protected $sndfile, protected $sfinfo) {}

    public static function read(string $filename): static
    {
        $sfinfo = Sndfile::new('SF_INFO');

        $sndfile = Sndfile::open($filename, Sndfile::enum('SFM_READ'), FFI::addr($sfinfo));

        return new static($sndfile, $sfinfo);
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

    public function toTensor(int $samplerate = 41000, int $chunkSize = 2048): Tensor
    {
        $tensorData = '';
        $totalOutputFrames = 0;

        $state = Samplerate::srcNew(Samplerate::enum('SRC_SINC_FASTEST'), $this->channels());

        $inputSize = $chunkSize * $this->channels();
        $inputData = Samplerate::new("float[$inputSize]");
        $outputSize = $chunkSize * $this->channels();
        $outputData = Samplerate::new("float[$outputSize]");

        $srcData = Samplerate::new('SRC_DATA');
        $srcData->data_in = Samplerate::cast('float *', $inputData);
        $srcData->output_frames = $chunkSize / $this->channels();
        $srcData->data_out = Samplerate::cast('float *', $outputData);
        $srcData->src_ratio = $samplerate / $this->samplerate();

        while (true) {
            /* Read the chunk of data */
            $srcData->input_frames = Sndfile::readFrames($this->sndfile, $inputData, $chunkSize);

            /* Add to tensor data without resample if the sample rate is the same */
            if ($this->samplerate() === $samplerate) {
                $strBuffer = FFI::string($inputData, $srcData->input_frames * $this->channels() * FFI::sizeof($inputData[0]));
                $tensorData .= $strBuffer;
                $totalOutputFrames += $srcData->input_frames;
                if ($srcData->input_frames < $chunkSize) {
                    break;
                }
                continue;
            }

            /* The last read will not be a full buffer, so snd_of_input. */
            if ($srcData->input_frames < $chunkSize) {
                $srcData->end_of_input = Sndfile::enum('SF_TRUE');
            }

            /* Process current block. */
            Samplerate::srcProcess($state, FFI::addr($srcData));

            /* Terminate if done. */
            if ($srcData->end_of_input && $srcData->output_frames_gen === 0) {
                break;
            }

            /* Add the processed data to the tensor data */
            $outputSize = $srcData->output_frames_gen * $this->channels() * FFI::sizeof($outputData[0]);
            $strBuffer = FFI::string($outputData, $outputSize);
            $tensorData .= $strBuffer;
            $totalOutputFrames += $srcData->output_frames_gen;
        }

        Samplerate::srcDelete($state);

        $audioTensor = Tensor::fromString($tensorData, Tensor::float32, [$totalOutputFrames, $this->channels()]);

        if ($this->channels() > 1) {
            $audioTensor = $audioTensor->mean(1);
        }

        return $audioTensor->squeeze();
    }

    public function fromTensor(Tensor $tensor): void
    {
        $size = $tensor->size();
        $buffer = Sndfile::new("float[$size]");
        $bufferString = $tensor->toString();
        $buffer->cdata = Sndfile::cast('float *', (int)$bufferString);

        $write = Sndfile::writeFrames($this->sndfile, $buffer, $size);

        if ($write !== $size) {
            throw new RuntimeException("Failed to write to file");
        }
    }


    public function __destruct()
    {
        Sndfile::close($this->sndfile);
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
     *
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
            $fftFreqs = self::hertzToMel(array_map(fn ($i) => $i * $fft_bin_width, range(0, $nFrequencyBins - 1)), $melScale);
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
                    $melFilters[$i][$j] *= $enorm;
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
     *
     * @param float[] $fftFreqs Discrete frequencies of the FFT bins in Hz, of shape `(num_frequency_bins,)`.
     * @param float[] $filterFreqs Center frequencies of the triangular filters to create, in Hz, of shape `(num_mel_filters,)`.
     *
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
     *
     * @param float $start The starting value of the sequence.
     * @param float $end The end value of the sequence.
     * @param int $num Number of samples to generate.
     *
     * @return float[] `num` evenly spaced samples, calculated over the interval `[start, stop]`.
     */
    private static function linspace(float $start, float $end, int $num): array
    {
        $step = ($end - $start) / ($num - 1);
        return array_map(fn ($i) => $start + $step * $i, range(0, $num - 1));
    }

    public static function hertzToMel(array|float|int $hz, string $melScale = "htk"): float|int|array
    {
        if (is_array($hz)) {
            return array_map(fn ($i) => self::hertzToMel($i, $melScale), $hz);
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
            return array_map(fn ($i) => self::melToHertz($i, $melScale), $mel);
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
        Tensor $spectrogram,
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
            throw new InvalidArgumentException('minValue must be greater than zero');
        }

        $reference = max($minValue, $reference);
        $logReference = log10($reference);

//        for ($i = 0; $i < count($spectrogram); $i++) {
//            $spectrogram->buffer()[$i] = $factor * log10(max($minValue, $spectrogram->buffer()[$i]) - $logReference);
//        }
        $spectrogram->u(fn ($x) => $factor * log10(max($minValue, $x) - $logReference));

        if ($dbRange !== null) {
            if ($dbRange <= 0) {
                throw new InvalidArgumentException('db_range must be greater than zero');
            }

            $maxValue = $spectrogram->max() - $dbRange;

//            for ($i = 0; $i < count($spectrogram); $i++) {
//                $spectrogram->buffer()[$i] = max($spectrogram->buffer()[$i], $maxValue);
//            }
            $spectrogram->u(fn ($x) => max($x, $maxValue));
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
     *
     * @return SplFixedArray
     */
    public static function amplitudeToDB(
        Tensor $spectrogram,
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
     *
     * @return SplFixedArray
     */
    public static function powerToDB(
        Tensor $spectrogram,
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
        Tensor  $waveform,
        Tensor  $window,
        int     $frameLength,
        int     $hopLength,
        ?int    $fftLength = null,
        float   $power = 1.0,
        bool    $center = true,
        string  $padMode = 'reflect',
        bool    $onesided = true,
        float   $preemphasis = 0,
        ?array  $melFilters = null,
        float   $melFloor = 1e-10,
        ?string $logMel = null,
        float   $reference = 1.0,
        float   $minValue = 1e-10,
        ?float  $dbRange = null,
        ?bool   $removeDcOffset = null,
        ?int    $maxNumFrames = null, // -1 for c
        bool    $doPad = true,
        bool    $transpose = false
    ): Tensor
    {
        $fftLength ??= $frameLength;
        if ($frameLength > $fftLength) {
            throw new InvalidArgumentException("frameLength ($frameLength) may not be larger than fftLength ($fftLength)");
        }

        $windowLength = $window->size();
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

            $halfWindow = (int)floor(($fftLength - 1) / 2) + 1;
            $paddedLength = $waveform->size() + (2 * $halfWindow);

            $padded = FastTransformersUtils::padReflect(
                $waveform->buffer()->addr($waveform->offset()),
                $waveform->size(),
                $paddedLength
            );

            $paddedStr = FFI::string($padded, FFI::sizeof($padded));

            $waveform = Tensor::fromString($paddedStr, $waveform->dtype(), [$paddedLength]);
        }

        $numFrames = 1 + floor(($waveform->size() - $frameLength) / $hopLength);
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

        $melFilters = Tensor::fromArray($melFilters, Tensor::float32);
        $spectrogramShape = $transpose ? [$d1Max, $melFilters->count()] : [$melFilters->count(), $d1Max];
        $logMel = match ($logMel) {
            'log' => FastTransformersUtils::enum('LOG_MEL_LOG'),
            'log10' => FastTransformersUtils::enum('LOG_MEL_LOG10'),
            'dB' => FastTransformersUtils::enum('LOG_MEL_DB'),
            default => FastTransformersUtils::enum('LOG_MEL_NONE'),
        };

        $spectrogram = FastTransformersUtils::spectrogram(
            $waveform->buffer()->addr($waveform->offset()),
            $waveform->size(),
            array_product($spectrogramShape),
            $hopLength,
            $fftLength,
            $window->buffer()->addr($window->offset()),
            $windowLength,
            $d1,
            $d1Max,
            $power,
            $center,
            $preemphasis,
            $melFilters->buffer()->addr($melFilters->offset()),
            $melFilters->count(),
            $numFrequencyBins,
            $melFloor,
            $logMel,
            $removeDcOffset,
            $doPad,
            $transpose,
        );

        $spectrogramStr = FFI::string($spectrogram, FFI::sizeof($spectrogram));

        return Tensor::fromString($spectrogramStr, $waveform->dtype(), $spectrogramShape);
    }

    /**
     * Generates a Hanning window of length M.
     *
     * @param int $M The length of the Hanning window to generate.
     *
     * @return Tensor The generated Hanning window.
     */
    private static function hanning(int $M): Tensor
    {
        if ($M < 1) {
            return Tensor::zeros([1]);
        }
        if ($M === 1) {
            return Tensor::ones([1]);
        }

        $denominator = $M - 1;
        $factor = M_PI / $denominator;

        $cosValues = Tensor::zeros([$M]);
        for ($i = 0; $i < $M; ++$i) {
            $n = 2 * $i - $denominator;
            $cosValues[$i] = 0.5 + 0.5 * cos($factor * $n);
        }

        return $cosValues;
    }

    /**
     * Returns an array containing the specified window.
     *
     * @param int $windowLength The length of the window in samples.
     * @param string $name The name of the window function.
     * @param bool $periodic Whether the window is periodic or symmetric.
     * @param int|null $frameLength The length of the analysis frames in samples.
     * Provide a value for `frame_length` if the window is smaller than the frame length, so that it will be zero-padded.
     * @param bool $center Whether to center the window inside the FFT buffer. Only used when `frameLength` is provided.
     *
     * @return Tensor The window of shape `(windowLength)` or `(frameLength)`.
     */
    public static function windowFunction(
        int    $windowLength,
        string $name,
        bool   $periodic = true,
        int    $frameLength = null,
        bool   $center = true
    ): Tensor
    {

        $length = $periodic ? $windowLength + 1 : $windowLength;

        $window = match ($name) {
            'boxcar' => Tensor::ones([$length]),
            'hann', 'hann_window' => self::hanning($length),
            'povey' => self::hanning($length)->pow(0.85),
            default => throw new InvalidArgumentException("Unknown window type $name."),
        };

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