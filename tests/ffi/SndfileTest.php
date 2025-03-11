<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\FFI\Sndfile;
use FFI\CData;

beforeEach(function () {
    if (!extension_loaded('ffi')) {
        $this->markTestSkipped('FFI extension is not loaded.');
    }

    $this->testSoundFile = __DIR__.'/../sounds/jfk.wav';
    $this->outputSoundFile = __DIR__.'/../sounds/output.wav';
});

it('loads the FFI instance', function () {
    $ffi = Sndfile::ffi();
    expect($ffi)->toBeInstanceOf(FFI::class);
});

it('can get the version of the library', function () {
    $version = Sndfile::version();
    expect($version)->toBeString();
});

it('can create a new instance of any type from the library', function () {
    $instance = Sndfile::new('SF_INFO');
    expect($instance)->toBeInstanceOf(CData::class);
});

it('can cast a pointer to a different type', function () {
    $data = Sndfile::new("float[2]");
    $float = Sndfile::cast("float*", $data);
    expect($float)->toBeInstanceOf(CData::class);
});

it('can retrieve the value of an enum constant', function () {
    $value = Sndfile::enum('SF_FORMAT_PCM_16');
    expect($value)->toBeInt();
});

it('can open a sound file', function () {
    $sfinfo = Sndfile::new('SF_INFO');
    $sndfile = Sndfile::open($this->testSoundFile, Sndfile::enum('SFM_READ'), \FFI::addr($sfinfo));

    expect($sndfile)->toBeInstanceOf(CData::class);

    Sndfile::close($sndfile);
});

it('can get the format of a sound file', function () {
    $sfinfo = Sndfile::new('SF_INFO');
    $sndfile = Sndfile::open($this->testSoundFile, Sndfile::enum('SFM_READ'), \FFI::addr($sfinfo));

    $format = Sndfile::getFormat($sndfile, $sfinfo);

    expect($format)->toBe("WAV (Microsoft)");

    Sndfile::close($sndfile);
});

it('can read frames from a sound file', function () {
    $sfinfo = Sndfile::new('SF_INFO');
    $sndfile = Sndfile::open($this->testSoundFile, Sndfile::enum('SFM_READ'), \FFI::addr($sfinfo));

    $chunkSize = 2048;
    $bufferSize = $chunkSize * 2;
    $buffer = Sndfile::new("float[{$bufferSize}]");
    $framesRead = Sndfile::readFrames($sndfile, $buffer, $chunkSize);

    expect($framesRead)->toBe($chunkSize)
        ->and($buffer[0])->toBeFloat();

    Sndfile::close($sndfile);
});

it('can write frames to a sound file', function () {


    $sfinfo = Sndfile::new('SF_INFO');
    $sfinfo->channels = 1;
    $sfinfo->samplerate = 44100;
    $sfinfo->format = Sndfile::enum('SF_FORMAT_WAV') | Sndfile::enum('SF_FORMAT_PCM_16');

    // Open file for writing
    $sndfile = Sndfile::open($this->outputSoundFile, Sndfile::enum('SFM_WRITE'), \FFI::addr($sfinfo));

    $chunkSize = 44100;
    $bufferSize = $chunkSize * 2;
    $buffer = Sndfile::new("float[{$bufferSize}]");
    $data = array_map(fn ($i) => 0.5 * sin(2 * pi() * 880 * $i / 44100), range(0, $bufferSize - 1));

    for ($i = 0; $i < $bufferSize; $i++) {
        $buffer[$i] = $data[$i];
    }

    $framesWritten = Sndfile::writeFrames($sndfile, $buffer, $chunkSize);

    expect($framesWritten)->toBe($chunkSize);

    Sndfile::close($sndfile);

    @unlink($this->outputSoundFile);
});
