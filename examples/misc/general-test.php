<?php

use Codewithkyrian\Transformers\FFI\TransformersUtils;
use Codewithkyrian\Transformers\FFI\Libc;
use Codewithkyrian\Transformers\FFI\OnnxRuntime;
use Codewithkyrian\Transformers\FFI\Samplerate;
use Codewithkyrian\Transformers\FFI\Sndfile;

include __DIR__.'/../vendor/autoload.php';

OnnxRuntime::version();
dd(\Codewithkyrian\Transformers\Utils\timeUsage(true));

dd(
    Libc::version(),
    Sndfile::version(),
    Samplerate::version(),
    OnnxRuntime::version(),
    TransformersUtils::version()
);
