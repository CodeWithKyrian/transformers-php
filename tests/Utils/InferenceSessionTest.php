<?php

use Codewithkyrian\Transformers\Utils\InferenceSession;
use Codewithkyrian\Transformers\Tensor\Tensor;

it('runs a model and returns expected output', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx');
    $input = Tensor::fromArray([[5.8, 2.8]], Tensor::float32);
    $output = $session->run(['label', 'probabilities'], ['input' => $input]);
    expect($output)->toBeArray();
    expect($output['label']->shape())->toBe([1]);
    expect($output['label']->toArray())->toBe([1]);
    expect($output['probabilities'])->toBeArray(); // Should be a sequence/map
});

it('returns available providers', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx');
    $providers = $session->providers();
    expect($providers)->toContain('CPUExecutionProvider');
});

it('ignores unavailable CUDA provider', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx', providers: ['CUDAExecutionProvider', 'CPUExecutionProvider']);
    $providers = $session->providers();
    expect($providers)->not->toContain('CUDAExecutionProvider');
});

it('supports profiling and returns a profile file', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx', enableProfiling: true);
    $file = $session->endProfiling();
    expect($file)->toContain('.json');
    if (file_exists($file)) {
        unlink($file);
    }
});

it('supports custom profile file prefix', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx', enableProfiling: true, profileFilePrefix: 'hello');
    $file = $session->endProfiling();
    expect($file)->toContain('hello');
    if (file_exists($file)) {
        unlink($file);
    }
});


it('returns correct input and output signatures for hello_world.onnx', function () {
    $session = new InferenceSession('tests/fixtures/models/hello_world.onnx');
    expect($session->inputs())->toBe([
        ['name' => 'x', 'type' => 'tensor(float)', 'shape' => [3, 4, 5]]
    ]);
    expect($session->outputs())->toBe([
        ['name' => 'y', 'type' => 'tensor(float)', 'shape' => [3, 4, 5]]
    ]);
});

it('predicts correctly for a float tensor input', function () {
    $session = new InferenceSession('tests/fixtures/models/hello_world.onnx');
    $x = [
        [
            [0.5488135,  0.71518934, 0.60276335, 0.5448832,  0.4236548],
            [0.6458941,  0.4375872,  0.891773,   0.96366274, 0.3834415],
            [0.79172504, 0.5288949,  0.56804454, 0.92559665, 0.07103606],
            [0.0871293,  0.0202184,  0.83261985, 0.77815676, 0.87001216]
        ],

        [
            [0.9786183,  0.7991586,  0.46147937, 0.7805292,  0.11827443],
            [0.639921,   0.14335328, 0.9446689,  0.5218483,  0.41466194],
            [0.2645556,  0.7742337,  0.45615032, 0.56843394, 0.0187898],
            [0.6176355,  0.6120957,  0.616934,   0.94374806, 0.6818203]
        ],

        [
            [0.3595079,  0.43703195, 0.6976312,  0.06022547, 0.6667667],
            [0.67063785, 0.21038257, 0.12892629, 0.31542835, 0.36371076],
            [0.57019675, 0.43860152, 0.9883738,  0.10204481, 0.20887676],
            [0.16130951, 0.6531083,  0.2532916,  0.46631077, 0.2444256]
        ]
    ];
    $output = $session->run(['y'], ['x' => Tensor::fromArray($x, Tensor::float32)]);
    expect($output['y'][0][0]->toArray())->toEqualWithDelta([0.6338603, 0.6715468, 0.6462883, 0.6329476, 0.6043575], 0.00001);
});

it('handles boolean input/output', function () {
    $session = new InferenceSession('tests/fixtures/models/logical_and.onnx');
    $x = [[false, false], [true, true]];
    $x2 = [[true, false], [true, false]];
    $output = $session->run(['output:0'], [
        'input:0' => Tensor::fromArray($x, Tensor::bool),
        'input1:0' => Tensor::fromArray($x2, Tensor::bool)
    ]);
    expect($output['output:0']->toArray())->toBe([[false, false], [true, false]]);
});

it('can load a model from a stream', function () {
    $stream = fopen('tests/fixtures/models/hello_world.onnx', 'rb');
    $session = new InferenceSession($stream);
    expect($session->inputs())->toBe([
        ['name' => 'x', 'type' => 'tensor(float)', 'shape' => [3, 4, 5]]
    ]);
});

it('predicts correctly for lightgbm.onnx and checks probabilities', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx');
    $x = [[5.8, 2.8]];
    $output = $session->run(['label', 'probabilities'], ['input' => Tensor::fromArray($x, Tensor::float32)]);
    expect($output['label']->toArray())->toBe([1]);
    $probabilities = $output['probabilities'][0];
    expect(array_column($probabilities->toArray(), 0))->toBe([0.0, 1.0, 2.0]);
    expect(array_column($probabilities->toArray(), 1))->toEqualWithDelta([0.2593829035758972, 0.409047931432724, 0.3315691649913788], 0.00001);
});

it('predicts correctly for randomforest.onnx', function () {
    $session = new InferenceSession('tests/fixtures/models/randomforest.onnx');
    $x = [[5.8, 2.8, 5.1, 2.4]];
    $output = $session->run(['output_label', 'output_probability'], ['float_input' => Tensor::fromArray($x, Tensor::float32)]);
    expect($output['output_label']->toArray())->toBe([2]);
    $probabilities = $output['output_probability'][0];
    expect(array_column($probabilities->toArray(), 0))->toBe([0.0, 1.0, 2.0]);
    expect(array_column($probabilities->toArray(), 1))->toEqualWithDelta([0.0, 0.0, 1.0000001192092896], 0.00001);
});

it('returns only requested output names', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx');
    $output = $session->run(['label'], ['input' => Tensor::fromArray([[5.8, 2.8]], Tensor::float32)]);
    expect(array_keys($output))->toBe(['label']);
});



it('handles session options and optimized model file', function () {
    $optimizedPath = tempnam(sys_get_temp_dir(), 'optimized');
    $session = new InferenceSession(
        'tests/fixtures/models/lightgbm.onnx',
        executionMode: null, // Set as needed
        graphOptimizationLevel: null, // Set as needed
        interOpNumThreads: 1,
        intraOpNumThreads: 1,
        logSeverityLevel: 4,
        logVerbosityLevel: 4,
        logid: 'test',
        optimizedModelFilepath: $optimizedPath
    );
    $x = [[5.8, 2.8]];
    $session->run(['label', 'probabilities'], ['input' => Tensor::fromArray($x, Tensor::float32)]);
    expect(file_get_contents($optimizedPath))->toContain('onnx');
});

it('handles free dimension overrides by denotation', function () {
    $session = new InferenceSession('tests/fixtures/models/abs_free_dimensions.onnx', freeDimensionOverridesByDenotation: ['DATA_BATCH' => 3, 'DATA_CHANNEL' => 5]);
    expect($session->inputs()[0]['shape'])->toBe([3, 5, 5]);
});

it('handles free dimension overrides by name', function () {
    $session = new InferenceSession('tests/fixtures/models/abs_free_dimensions.onnx', freeDimensionOverridesByName: ['Dim1' => 4, 'Dim2' => 6]);
    expect($session->inputs()[0]['shape'])->toBe([4, 6, 5]);
});

it('returns input shape names for symbolic dimensions', function () {
    $session = new InferenceSession('tests/fixtures/models/abs_free_dimensions.onnx');
    expect($session->inputs()[0]['shape'])->toBe(['Dim1', 'Dim2', 5]);
});

it('handles session config entries', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx', sessionConfigEntries: ['key' => 'value']);
    expect($session)->toBeInstanceOf(InferenceSession::class);
});

it('handles run options', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx');
    $x = [[5.8, 2.8]];
    $session->run(['label', 'probabilities'], ['input' => Tensor::fromArray($x, Tensor::float32)], logSeverityLevel: 4, logVerbosityLevel: 4, logid: 'test', terminate: false);
    expect(true)->toBeTrue();
});

it('throws on invalid rank', function () {
    $session = new InferenceSession('tests/fixtures/models/hello_world.onnx');
    expect(fn() => $session->run(['y'], ['x' => Tensor::fromArray([1], Tensor::float32)]))
        ->toThrow(Exception::class);
});

it('throws on invalid dimensions', function () {
    $session = new InferenceSession('tests/fixtures/models/hello_world.onnx');
    expect(fn() => $session->run(['y'], ['x' => Tensor::fromArray([[[1]],], Tensor::float32)]))
        ->toThrow(Exception::class);
});

it('throws on missing input', function () {
    $session = new InferenceSession('tests/fixtures/models/hello_world.onnx');
    expect(fn() => $session->run(['y'], []))
        ->toThrow(Exception::class);
});

it('throws on extra input', function () {
    $session = new InferenceSession('tests/fixtures/models/hello_world.onnx');
    expect(fn() => $session->run(['y'], ['x' => Tensor::fromArray([1], Tensor::float32), 'y' => Tensor::fromArray([1], Tensor::float32)]))
        ->toThrow(Exception::class);
});

it('throws on invalid output name', function () {
    $session = new InferenceSession('tests/fixtures/models/lightgbm.onnx');
    $x = [[5.8, 2.8]];
    expect(fn() => $session->run(['bad'], ['input' => Tensor::fromArray($x, Tensor::float32)]))
        ->toThrow(Exception::class);
});

it('returns model metadata', function () {
    $session = new InferenceSession('tests/fixtures/models/hello_world.onnx');
    $metadata = $session->modelmeta();
    expect($metadata['custom_metadata_map'])->toEqual(['hello' => 'world', 'test' => 'value']);
    expect($metadata['description'])->toBe('');
    expect($metadata['domain'])->toBe('');
    expect($metadata['graph_name'])->toBe('test_sigmoid');
    expect($metadata['producer_name'])->toBe('backend-test');
    expect($metadata['version'])->toBe(9223372036854775807);
});
