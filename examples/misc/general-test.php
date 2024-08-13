<?php

use Codewithkyrian\Transformers\FFI\FastTransformersUtils;
use Codewithkyrian\Transformers\FFI\Libc;
use Codewithkyrian\Transformers\FFI\OnnxRuntime;
use Codewithkyrian\Transformers\FFI\Samplerate;
use Codewithkyrian\Transformers\FFI\Sndfile;
use Codewithkyrian\Transformers\Transformers;

include __DIR__.'/../vendor/autoload.php';

Transformers::setup();

dd(
    Libc::version(),
    Sndfile::version(),
    Samplerate::version(),
    OnnxRuntime::version(),
    FastTransformersUtils::version()
);
