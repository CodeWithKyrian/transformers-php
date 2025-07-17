<?php

use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Tensor\Tensor;

describe('VIPS Driver', function () {
    beforeEach(function () {
        Transformers::setup()->setImageDriver(ImageDriver::VIPS);
    });

    it('can read and get dimensions', function () {
        $img = Image::read('tests/fixtures/images/sample.jpg');
        expect($img->width())->toBeInt()->and($img->height())->toBeInt();
        expect($img->size())->toHaveCount(2);
    });

    it('can resize and thumbnail', function () {
        $img = Image::read('tests/fixtures/images/sample.jpg');
        $resized = $img->resize(64, 32);
        expect($resized->width())->toBe(64);
        expect($resized->height())->toBe(32);
        $thumb = $img->thumbnail(32, 32);
        expect($thumb->width())->toBeLessThanOrEqual(32);
        expect($thumb->height())->toBeLessThanOrEqual(32);
    });

    it('can grayscale, rgb, and rgba', function () {
        $img = Image::read('tests/fixtures/images/sample.jpg');
        $gray = $img->grayscale();
        expect($gray->channels)->toBe(1);
        $rgb = $img->rgb();
        expect($rgb->channels)->toBe(3);
        $rgba = $img->rgba();
        expect($rgba->channels)->toBe(4);
    });

    it('can center crop and crop', function () {
        $img = Image::read('tests/fixtures/images/sample.jpg');
        $center = $img->centerCrop(32, 32);
        expect($center->width())->toBe(32);
        expect($center->height())->toBe(32);
        $crop = $img->crop(0, 0, 15, 15);
        expect($crop->width())->toBe(16);
        expect($crop->height())->toBe(16);
    });

    it('can pad and invert', function () {
        $img = Image::read('tests/fixtures/images/sample.jpg');
        $padded = $img->pad(2, 2, 2, 2);
        expect($padded->width())->toBe($img->width() + 4);
        expect($padded->height())->toBe($img->height() + 4);
        $inverted = $img->invert();
        expect($inverted)->toBeInstanceOf(Image::class);
    });

    it('can convert to and from tensor', function () {
        $img = Image::read('tests/fixtures/images/sample.jpg');
        $tensor = $img->toTensor('CHW');
        expect($tensor)->toBeInstanceOf(Tensor::class);
        $img2 = Image::fromTensor($tensor, 'CHW');
        expect($img2->width())->toBe($img->width());
        expect($img2->height())->toBe($img->height());
        expect($img2->channels)->toBe($img->channels);
    });

    it('can save an image', function () {
        $img = Image::read('tests/fixtures/images/sample.jpg');
        $tmp = tempnam(sys_get_temp_dir(), 'imgtest_') . '.jpg';
        $img->save($tmp);
        expect(file_exists($tmp))->toBeTrue();
        unlink($tmp);
    });
});
