<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\FFI\Samplerate;
use FFI\CData;

beforeEach(function () {
    if (!extension_loaded('ffi')) {
        $this->markTestSkipped('FFI extension is not loaded.');
    }
});

it('loads the FFI instance', function () {
    $ffi = Samplerate::ffi();
    expect($ffi)->toBeInstanceOf(FFI::class);
});

it('can get the version of the library', function () {
    $version = Samplerate::version();
    expect($version)->toBeString();
});

it('can create a new instance of any type from the library', function () {
    $instance = Samplerate::new('SRC_DATA');
    expect($instance)->toBeInstanceOf(CData::class);
});

it('can cast a pointer to a different type', function () {
    $data = Samplerate::new("float[2]");
    $castedInstance = Samplerate::cast('float *', $data);
    expect($castedInstance)->toBeInstanceOf(CData::class);
});

it('can retrieve the value of an enum constant', function () {
    $value = Samplerate::enum('SRC_SINC_FASTEST');
    expect($value)->toBeInt();
});

it('can create a new sample rate converter', function () {
    $converterType = Samplerate::enum('SRC_SINC_FASTEST');
    $state = Samplerate::srcNew($converterType, 2);

    expect($state)->toBeInstanceOf(CData::class);

    Samplerate::srcDelete($state);
});

it('can process data using a sample rate converter', function () {
    $converterType = Samplerate::enum('SRC_SINC_FASTEST');
    $state = Samplerate::srcNew($converterType, 1);

    $srcRatio = 0.2;
    $inputFrames = 1024;
    $outputFrames = 2048;

    $data = array_map(fn ($i) => 0.5 * sin(2 * pi() * 880 * $i / 44100), range(0, $inputFrames - 1));
    $dataIn = Samplerate::new("float[$inputFrames]");
    for ($i = 0; $i < $inputFrames; $i++) {
        $dataIn[$i] = $data[$i];
    }
    $dataOut = Samplerate::new("float[$outputFrames]");
    for ($i = 0; $i < $outputFrames; $i++) {
        $dataOut[$i] = 0.0;
    }

    $data = Samplerate::new('SRC_DATA');
    $data->data_in = Samplerate::cast('float *', $dataIn);
    $data->data_out = Samplerate::cast('float *', $dataOut);
    $data->input_frames = $inputFrames;
    $data->output_frames = $outputFrames;
    $data->src_ratio = $srcRatio;

    Samplerate::srcProcess($state, \FFI::addr($data));

    expect($data->output_frames_gen)
        ->toBeInt()
        ->toBeLessThan($inputFrames * $srcRatio);

    Samplerate::srcDelete($state);
});
