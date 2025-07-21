<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\FFI\Samplerate;
use FFI\CData;

beforeEach(function () {
    if (!extension_loaded('ffi')) {
        $this->markTestSkipped('FFI extension is not loaded.');
    }
    $this->samplerate = new Samplerate();
});

it('loads the FFI instance', function () {
    $ffi = $this->samplerate->getFFI();
    expect($ffi)->toBeInstanceOf(FFI::class);
});

it('can get the version of the library', function () {
    $version = $this->samplerate->version();
    expect($version)->toBeString();
});

it('can create a new instance of any type from the library', function () {
    $instance = $this->samplerate->new('SRC_DATA');
    expect($instance)->toBeInstanceOf(CData::class);
});

it('can cast a pointer to a different type', function () {
    $data = $this->samplerate->new("float[2]");
    $castedInstance = $this->samplerate->cast('float *', $data);
    expect($castedInstance)->toBeInstanceOf(CData::class);
});

it('can retrieve the value of an enum constant', function () {
    $value = $this->samplerate->enum('SRC_SINC_FASTEST');
    expect($value)->toBeInt();
});

it('can create a new sample rate converter', function () {
    $converterType = $this->samplerate->enum('SRC_SINC_FASTEST');
    $state = $this->samplerate->src_new($converterType, 2);

    expect($state)->toBeInstanceOf(CData::class);

    $this->samplerate->delete($state);
});

it('can process data using a sample rate converter', function () {
    $converterType = $this->samplerate->enum('SRC_SINC_FASTEST');
    $state = $this->samplerate->src_new($converterType, 1);

    $srcRatio = 0.2;
    $inputFrames = 1024;
    $outputFrames = 2048;

    $data = array_map(fn ($i) => 0.5 * sin(2 * pi() * 880 * $i / 44100), range(0, $inputFrames - 1));
    $dataIn = $this->samplerate->new("float[$inputFrames]");
    for ($i = 0; $i < $inputFrames; $i++) {
        $dataIn[$i] = $data[$i];
    }
    $dataOut = $this->samplerate->new("float[$outputFrames]");
    for ($i = 0; $i < $outputFrames; $i++) {
        $dataOut[$i] = 0.0;
    }

    $data = $this->samplerate->new('SRC_DATA');
    $data->data_in = $this->samplerate->cast('float *', $dataIn);
    $data->data_out = $this->samplerate->cast('float *', $dataOut);
    $data->input_frames = $inputFrames;
    $data->output_frames = $outputFrames;
    $data->src_ratio = $srcRatio;
    $data->end_of_input = 0;

    $this->samplerate->process($state, \FFI::addr($data));

    expect($data->output_frames_gen)
        ->toBeInt()
        ->toBeLessThanOrEqual((int)ceil($inputFrames * $srcRatio));

    $this->samplerate->delete($state);
});
