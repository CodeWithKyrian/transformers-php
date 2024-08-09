<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

use Codewithkyrian\Transformers\Transformers;
use RuntimeException;
use function Codewithkyrian\Transformers\Utils\joinPaths;

enum Lib
{
    case OnnxRuntime;
    case OpenBlas;
    case RindowMatlib;
    case Sndfile;
    case Samplerate;
    case FastTransformersUtils;


    public function header(): string
    {
        $filename = match ($this) {
            self::OnnxRuntime => 'onnxruntime.h',
            self::OpenBlas => 'openblas.h',
            self::RindowMatlib => 'matlib.h',
            self::Sndfile => 'sndfile.h',
            self::Samplerate => 'samplerate.h',
            self::FastTransformersUtils => 'fast_transformers_utils.h',
        };

        $headerFile = joinPaths(__DIR__, '..', '..', 'includes', $filename);

        if (!file_exists($headerFile)) {
            throw new RuntimeException('Header file not found: '.$filename);
        }

        return $headerFile;
    }

    public function library(): string
    {
        $libraryName = match ($this) {
            self::OnnxRuntime => 'libonnxruntime',
            self::OpenBlas => 'libopenblas',
            self::RindowMatlib => 'librindowmatlib',
            self::Sndfile => 'libsndfile',
            self::Samplerate => 'libsamplerate',
            self::FastTransformersUtils => 'libfast_transformers_utils',
        };

        $resolver = Transformers::getLibraryResolver();

        if (!$resolver->exists($libraryName)) {
            throw new RuntimeException("Library not found: $libraryName");
        }

        return $resolver->resolve($libraryName);
    }
}
