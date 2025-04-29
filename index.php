<?php

require_once __DIR__ . '/vendor/autoload.php';

use Codewithkyrian\Transformers\FFI\OpenBLAS;
use Codewithkyrian\Transformers\FFI\Libvips;
use Codewithkyrian\Transformers\FFI\OnnxRuntime;
use Codewithkyrian\Transformers\FFI\RindowMatlib;
use Codewithkyrian\Transformers\FFI\Samplerate;
use Codewithkyrian\Transformers\FFI\Sndfile;
use Codewithkyrian\Transformers\FFI\TransformersUtils;

$onnx = new OpenBLAS();
$vips = new Libvips();
$onnxRuntime = new OnnxRuntime();
$rindowMatlib = new RindowMatlib();
$samplerate = new Samplerate();
$sndfile = new Sndfile();
$transformersUtils = new TransformersUtils();


dump("Found OpenBLAS? " . (file_exists($onnx->getLibraryPath()) ? 'Yes' : 'No'));
dump("Found Libvips? " . (file_exists($vips->getLibraryPath()) ? 'Yes' : 'No'));
dump("Found OnnxRuntime? " . (file_exists($onnxRuntime->getLibraryPath()) ? 'Yes' : 'No'));
dump("Found RindowMatlib? " . (file_exists($rindowMatlib->getLibraryPath()) ? 'Yes' : 'No'));
dump("Found Samplerate? " . (file_exists($samplerate->getLibraryPath()) ? 'Yes' : 'No'));
dump("Found Sndfile? " . (file_exists($sndfile->getLibraryPath()) ? 'Yes' : 'No'));
dump("Found TransformersUtils? " . (file_exists($transformersUtils->getLibraryPath()) ? 'Yes' : 'No'));