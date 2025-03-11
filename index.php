<?php

require_once __DIR__ . '/vendor/autoload.php';

use Codewithkyrian\Transformers\FFI\OnnxRuntime;
use Codewithkyrian\Transformers\FFI\Samplerate;
use Codewithkyrian\Transformers\FFI\Sndfile;

$samplerate = new Samplerate();

echo $samplerate->version();
