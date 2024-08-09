<?php

use Codewithkyrian\Transformers\FFI\Libs\FastTransformersUtils;
use Codewithkyrian\Transformers\FFI\Libs\Libc;
use Codewithkyrian\Transformers\FFI\Libs\Samplerate;
use Codewithkyrian\Transformers\FFI\Libs\Sndfile;
use Codewithkyrian\Transformers\FFI\Libs\OnnxRuntime;
use Codewithkyrian\Transformers\Tensor\OpenBLASFactory;
use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\TransformersLibrariesDownloader\Libraries;
use Rindow\Matlib\FFI\MatlibFactory;

include __DIR__.'/../vendor/autoload.php';

Transformers::setup();

dd(
    Libc::version(),
    Sndfile::version(),
    Samplerate::version(),
    OnnxRuntime::version(),
    FastTransformersUtils::version()
);
