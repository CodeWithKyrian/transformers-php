<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\FFI\Sndfile;
use FFI\CData;

beforeEach(function () {
    if (!extension_loaded('ffi')) {
        $this->markTestSkipped('FFI extension is not loaded.');
    }

    $this->testSoundFile = __DIR__ . '/../sounds/jfk.wav';
    $this->outputSoundFile = __DIR__ . '/../sounds/output.wav';
    
    $this->sndfile = new Sndfile();
});

it('loads the FFI instance', function () {
    $ffi = $this->sndfile->getFFI();
    expect($ffi)->toBeInstanceOf(FFI::class);
});

it('can create a new instance of any type from the library', function () {
    $instance = $this->sndfile->new('SF_INFO');
    expect($instance)->toBeInstanceOf(CData::class);
});

it('can cast a pointer to a different type', function () {
    $data = $this->sndfile->new("float[2]");
    $float = $this->sndfile->cast("float*", $data);
    expect($float)->toBeInstanceOf(CData::class);
});

it('can retrieve the value of an enum constant', function () {
    $value = $this->sndfile->enum('SF_FORMAT_PCM_16');
    expect($value)->toBeInt();
});

it('can open a sound file', function () {
    $sfinfo = $this->sndfile->new('SF_INFO');
    $sndfileHandle = $this->sndfile->open($this->testSoundFile, $this->sndfile->enum('SFM_READ'), \FFI::addr($sfinfo));

    expect($sndfileHandle)->toBeInstanceOf(CData::class);

    $this->sndfile->close($sndfileHandle);
});

it('can read frames from a sound file', function () {
    $sfinfo = $this->sndfile->new('SF_INFO');
    $sndfileHandle = $this->sndfile->open($this->testSoundFile, $this->sndfile->enum('SFM_READ'), \FFI::addr($sfinfo));

    $chunkSize = 2048;
    $bufferSize = $chunkSize * $sfinfo->channels;
    $buffer = $this->sndfile->new("float[{$bufferSize}]");
    $framesRead = $this->sndfile->readf_float($sndfileHandle, $buffer, $chunkSize);

    expect($framesRead)->toBe($chunkSize)
        ->and($buffer[0])->toBeFloat();

    $this->sndfile->close($sndfileHandle);
});

it('can write frames to a sound file', function () {
    $sfinfo = $this->sndfile->new('SF_INFO');
    $sfinfo->channels = 1;
    $sfinfo->samplerate = 44100;
    $sfinfo->format = $this->sndfile->enum('SF_FORMAT_WAV') | $this->sndfile->enum('SF_FORMAT_PCM_16');

    $sndfileHandle = $this->sndfile->open($this->outputSoundFile, $this->sndfile->enum('SFM_WRITE'), \FFI::addr($sfinfo));

    $chunkSize = 44100;
    $bufferSize = $chunkSize * $sfinfo->channels;
    $buffer = $this->sndfile->new("float[{$bufferSize}]");
    $data = array_map(fn ($i) => 0.5 * sin(2 * pi() * 880 * $i / 44100), range(0, $bufferSize - 1));

    for ($i = 0; $i < $bufferSize; $i++) {
        $buffer[$i] = $data[$i];
    }

    $framesWritten = $this->sndfile->writef_float($sndfileHandle, $buffer, $chunkSize);

    expect($framesWritten)->toBe($chunkSize);

    $this->sndfile->close($sndfileHandle);

    @unlink($this->outputSoundFile);
});
