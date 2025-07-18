<?php

/**
 * This file is a heavily modified version of the original file from the onnxruntime-php repository.
 *
 * Original source: https://github.com/ankane/onnxruntime-php/blob/master/src/InferenceSession.php
 * The original file is licensed under the MIT License.
 */

namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\FFI\Libc;
use Codewithkyrian\Transformers\FFI\OnnxRuntime;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Exception;
use FFI;
use FFI\CData;
use Codewithkyrian\Transformers\Transformers;

class InferenceSession
{
    private ?CData $session;
    private ?CData $allocator;
    private array $inputs;
    private array $outputs;
    private OnnxRuntime $ort;

    public function __construct(
        $path,
        $enableCpuMemArena = true,
        $enableMemPattern = true,
        $enableProfiling = false,
        $executionMode = null,
        $freeDimensionOverridesByDenotation = null,
        $freeDimensionOverridesByName = null,
        $graphOptimizationLevel = null,
        $interOpNumThreads = null,
        $intraOpNumThreads = null,
        $logSeverityLevel = null,
        $logVerbosityLevel = null,
        $logid = null,
        $optimizedModelFilepath = null,
        $profileFilePrefix = null,
        $sessionConfigEntries = null,
        $providers = []
    ) {
        $this->ort = new OnnxRuntime();
        //        $providers = ['CoreMLExecutionProvider', 'CPUExecutionProvider'];
        // session options
        $sessionOptions = $this->ort->CreateSessionOptions();

        if ($enableCpuMemArena) {
            $this->ort->EnableCpuMemArena($sessionOptions);
        } else {
            $this->ort->DisableCpuMemArena($sessionOptions);
        }
        if ($enableMemPattern) {
            $this->ort->EnableMemPattern($sessionOptions);
        } else {
            $this->ort->DisableMemPattern($sessionOptions);
        }
        if ($enableProfiling) {
            $this->ort->EnableProfiling($sessionOptions, $this->ortString($profileFilePrefix ?? 'onnxruntime_profile_'));
        } else {
            $this->ort->DisableProfiling($sessionOptions);
        }
        if (!is_null($executionMode)) {
            $this->ort->SetSessionExecutionMode($sessionOptions, $executionMode->value);
        }
        if (!is_null($freeDimensionOverridesByDenotation)) {
            foreach ($freeDimensionOverridesByDenotation as $k => $v) {
                $this->ort->AddFreeDimensionOverride($sessionOptions, $k, $v);
            }
        }
        if (!is_null($freeDimensionOverridesByName)) {
            foreach ($freeDimensionOverridesByName as $k => $v) {
                $this->ort->AddFreeDimensionOverrideByName($sessionOptions, $k, $v);
            }
        }
        if (!is_null($graphOptimizationLevel)) {
            $this->ort->SetSessionGraphOptimizationLevel($sessionOptions, $graphOptimizationLevel->value);
        }
        if (!is_null($interOpNumThreads)) {
            $this->ort->SetInterOpNumThreads($sessionOptions, $interOpNumThreads);
        }
        if (!is_null($intraOpNumThreads)) {
            $this->ort->SetIntraOpNumThreads($sessionOptions, $intraOpNumThreads);
        }
        if (!is_null($logSeverityLevel)) {
            $this->ort->SetSessionLogSeverityLevel($sessionOptions, $logSeverityLevel);
        }
        if (!is_null($logVerbosityLevel)) {
            $this->ort->SetSessionLogVerbosityLevel($sessionOptions, $logVerbosityLevel);
        }
        if (!is_null($logid)) {
            $this->ort->SetSessionLogId($sessionOptions, $logid);
        }
        if (!is_null($optimizedModelFilepath)) {
            $this->ort->SetOptimizedModelFilePath($sessionOptions, $this->ortString($optimizedModelFilepath));
        }
        if (!is_null($sessionConfigEntries)) {
            foreach ($sessionConfigEntries as $k => $v) {
                $this->ort->AddSessionConfigEntry($sessionOptions, $k, $v);
            }
        }

        foreach ($providers as $provider) {
            if (!in_array($provider, $this->providers())) {
                $logger = Transformers::getLogger();
                $logger->warning('Provider not available: ' . $provider);
                continue;
            }

            if ($provider == 'CUDAExecutionProvider') {
                $cudaOptions = $this->ort->CreateCUDAProviderOptions();
                $this->ort->SessionOptionsAppendExecutionProvider_CUDA_V2($sessionOptions, $cudaOptions);
                $this->ort->ReleaseCUDAProviderOptions($cudaOptions);
            } elseif ($provider == 'CoreMLExecutionProvider') {
                $this->ort->OrtSessionOptionsAppendExecutionProvider_CoreML($sessionOptions, 0);
            } elseif ($provider == 'CPUExecutionProvider') {
                break;
            } else {
                throw new \InvalidArgumentException('Provider not supported: ' . $provider);
            }
        }
        $this->session = $this->loadSession($path, $sessionOptions);
        $this->allocator = $this->ort->GetAllocatorWithDefaultOptions();
        $this->inputs = $this->loadInputs();
        $this->outputs = $this->loadOutputs();

        $this->ort->ReleaseSessionOptions($sessionOptions);
    }

    public function __destruct()
    {
        $this->ort->ReleaseSession($this->session);
    }

    /**
     * Runs the inference session with the provided inputs and outputs.
     *
     * @param array<string, Tensor> $inputFeed
     * @param array<string, Tensor> $outputNames
     * @param string|null $logSeverityLevel
     * @param string|null $logVerbosityLevel
     * @param string|null $logid
     * @param bool|null $terminate
     * @return array<string, Tensor> The output tensors.
     */
    public function run($outputNames, $inputFeed, $logSeverityLevel = null, $logVerbosityLevel = null, $logid = null, $terminate = null): array
    {
        // pointer references
        $refs = [];

        $inputTensor = $this->tensorArrayToOrtValueArray($inputFeed, $refs);
        $outputNames ??= array_map(fn($v) => $v['name'], $this->outputs);

        $inputNodeNames = $this->createCStringArray(array_keys($inputFeed), $refs);
        $outputNodeNames = $this->createCStringArray($outputNames, $refs);

        // run options
        $runOptions = $this->ort->CreateRunOptions();

        if (!is_null($logVerbosityLevel)) {
            $this->ort->RunOptionsSetRunLogSeverityLevel($runOptions, $logSeverityLevel);
        }
        if (!is_null($logVerbosityLevel)) {
            $this->ort->RunOptionsSetRunLogVerbosityLevel($runOptions, $logVerbosityLevel);
        }
        if (!is_null($logid)) {
            $this->ort->RunOptionsSetRunTag($runOptions, $logid);
        }
        if (!is_null($terminate)) {
            if ($terminate) {
                $this->ort->RunOptionsSetTerminate($runOptions);
            } else {
                $this->ort->RunOptionsUnsetTerminate($runOptions);
            }
        }

        $outputTensor = $this->ort->Run($this->session, $runOptions, $inputNodeNames, $inputTensor, count($inputFeed), $outputNodeNames, count($outputNames));

        $output = [];
        foreach ($outputTensor as $i => $t) {
            $output[$outputNames[$i]] = $this->ortValueToTensor($t);
        }

        // TODO use finally
        $this->ort->ReleaseRunOptions($runOptions);

        if ($inputTensor) {
            for ($i = 0; $i < count($inputFeed); $i++) {
                $this->ort->ReleaseValue($inputTensor[$i]);
            }
        }

        // output values released in ortValueToTensor

        return $output;
    }

    public function inputs(): array
    {
        return $this->inputs;
    }

    public function outputs(): array
    {
        return $this->outputs;
    }

    public function modelmeta(): array
    {
        $metadata = $this->ort->SessionGetModelMetadata($this->session);

        $customMetadataMap = [];
        [$keys, $numKeys] = $this->ort->ModelMetadataGetCustomMetadataMapKeys($metadata, $this->allocator);

        for ($i = 0; $i < $numKeys; $i++) {
            $key = $keys[$i];
            $value = $this->ort->ModelMetadataLookupCustomMetadataMap($metadata, $this->allocator, $key);
            $customMetadataMap[$key] = $value;
        }

        $description = $this->ort->ModelMetadataGetDescription($metadata, $this->allocator);
        $domain = $this->ort->ModelMetadataGetDomain($metadata, $this->allocator);
        $graphName = $this->ort->ModelMetadataGetGraphName($metadata, $this->allocator);
        //        $graphDescription = OnnxRuntime::ModelMetadataGetGraphDescription($metadata, $this->allocator);
        $producerName = $this->ort->ModelMetadataGetProducerName($metadata, $this->allocator);
        $version = $this->ort->ModelMetadataGetVersion($metadata);

        $result = [
            'custom_metadata_map' => $customMetadataMap,
            'description' => $description,
            'domain' => $domain,
            'graph_name' => $graphName,
            //            'graph_description' => $graphDescription,
            'producer_name' => $producerName,
            'version' => $version
        ];

        // TODO use finally
        $this->ort->ReleaseModelMetadata($metadata);

        return $result;
    }

    // return value has double underscore like Python
    public function endProfiling(): string
    {
        return $this->ort->SessionEndProfiling($this->session, $this->allocator);
    }

    // No way to set providers with C API yet, so we can return all available providers
    public function providers(): array
    {
        [$outPtr, $length] = $this->ort->GetAvailableProviders();

        $providers = [];

        for ($i = 0; $i < $length; $i++) {
            $providers[] = FFI::string($outPtr[$i]);
        }

        $this->ort->ReleaseAvailableProviders($outPtr, $length);

        return $providers;
    }

    private function loadSession($path, $sessionOptions): ?CData
    {
        if (is_resource($path) && get_resource_type($path) == 'stream') {
            $contents = stream_get_contents($path);
            $session = $this->ort->CreateSessionFromArray(self::env(), $contents, strlen($contents), $sessionOptions);
        } else {
            $session = $this->ort->CreateSession(self::env(), $this->ortString($path), $sessionOptions);
        }
        return $session;
    }

    private function loadInputs(): array
    {
        $inputs = [];
        $numInputNodes = $this->ort->SessionGetInputCount($this->session);

        for ($i = 0; $i < $numInputNodes; $i++) {
            $name = $this->ort->SessionGetInputName($this->session, $i, $this->allocator);

            $typeInfo = $this->ort->SessionGetInputTypeInfo($this->session, $i); // freed in nodeInfo

            $inputs[] = array_merge(['name' => $name], $this->nodeInfo($typeInfo));
        }

        return $inputs;
    }

    private function loadOutputs(): array
    {
        $outputs = [];
        $numOutputNodes = $this->ort->SessionGetOutputCount($this->session);

        for ($i = 0; $i < $numOutputNodes; $i++) {
            $name = $this->ort->SessionGetOutputName($this->session, $i, $this->allocator);

            $typeInfo = $this->ort->SessionGetOutputTypeInfo($this->session, $i); // freed in nodeInfo

            $outputs[] = array_merge(['name' => $name], $this->nodeInfo($typeInfo));
        }

        return $outputs;
    }

    /**
     * Convert internal input tensor to Onnx tensor
     *
     * @param array<string, Tensor> $inputFeed
     * @param array<int, CData> $refs
     * @return CData|null
     */
    private function tensorArrayToOrtValueArray($inputFeed, &$refs): ?CData
    {
        $allocatorInfo = $this->ort->CreateCpuMemoryInfo(1, 0);

        $inputFeedSize = count($inputFeed);
        if ($inputFeedSize == 0) {
            throw new Exception('No input');
        }

        $inputTensor = $this->ort->new("OrtValue*[$inputFeedSize]");

        $idx = 0;
        /* @var $input Tensor */
        foreach ($inputFeed as $inputName => $input) {
            $inp = null;
            foreach ($this->inputs as $i) {
                if ($i['name'] == $inputName) {
                    $inp = $i;
                    break;
                }
            }
            if (is_null($inp)) {
                throw new Exception("Unknown input: $inputName");
            }

            $shape = $input->shape();
            $ndim = $input->ndim();
            $size = $input->size();

            $inputNodeShape = $this->ort->new("int64_t[$ndim]");
            for ($i = 0; $i < $ndim; $i++) {
                $inputNodeShape[$i] = $shape[$i];
            }

            if ($inp['type'] == 'tensor(string)') {
                $inputTensorValues = $this->createCStringArray($input->toArray(), $refs);

                $typeEnum = $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING');
                $this->ort->CreateTensorAsOrtValue($this->allocator, $inputNodeShape, $ndim, $typeEnum, FFI::addr($inputTensor[$idx]));
                $this->ort->FillStringTensor($inputTensor[$idx], $inputTensorValues, $size);
            } else {

                $inputTypes = array_flip(array_map(fn($v) => "tensor($v)", $this->elementDataTypes()));

                if (isset($inputTypes[$inp['type']])) {
                    $typeEnum = $inputTypes[$inp['type']];
                    $castType = $this->castTypes()[$typeEnum];
                    $phpTensorType = $this->phpTensorTypes()[$typeEnum];
                    $input = $input->to($phpTensorType);
                } else {
                    $this->unsupportedType('input', $inp['type']);
                }

                if ($size === 0) {
                    $inputTensorValues = $this->ort->new("void *");
                } else {
                    $inputTensorValues = $this->ort->new("{$castType}[$size]");
                }

                $inputDump = $input->buffer()->dump();
                FFI::memcpy($inputTensorValues, $inputDump, strlen($inputDump));

                $this->ort->CreateTensorWithDataAsOrtValue($allocatorInfo, $inputTensorValues, FFI::sizeof($inputTensorValues), $inputNodeShape, $ndim, $typeEnum, FFI::addr($inputTensor[$idx]));
            }

            $refs[] = $inputNodeShape;
            $refs[] = $inputTensorValues;

            $idx++;
        }

        // TODO use finally
        $this->ort->ReleaseMemoryInfo($allocatorInfo);

        return $inputTensor;
    }

    private function createCStringArray(array $strings, array &$refs): CData
    {
        $arraySize = count($strings);
        $ptr = $this->ort->new("char*[$arraySize]");
        foreach ($strings as $i => $str) {
            $strPtr = Libc::cstring($str);
            $ptr[$i] = $strPtr;
            $refs[] = $strPtr;
        }
        return $ptr;
    }

    private function ortValueToTensor($outPtr)
    {
        try {
            $outType = $this->ort->GetValueType($outPtr);

            if ($outType->cdata == $this->ort->enum('ONNX_TYPE_TENSOR')) {
                $typeInfo = $this->ort->GetTensorTypeAndShape($outPtr);

                [$type, $shape] = $this->tensorTypeAndShape($typeInfo);

                // TODO skip if string
                $tensorData = $this->ort->GetTensorMutableData($outPtr);

                $outputTensorSize = $this->ort->GetTensorShapeElementCount($typeInfo);

                $this->ort->ReleaseTensorTypeAndShapeInfo($typeInfo);

                $castTypes = $this->castTypes();

                if (isset($castTypes[$type])) {
                    $arr = $this->ort->cast($castTypes[$type] . "[$outputTensorSize]", $tensorData);
                } elseif ($type == $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING')) {
                    $arr = $this->ortValueToStrings($outPtr, $outputTensorSize);
                } else {
                    $this->unsupportedType('element', $type);
                }

                $phpTensorType = $this->phpTensorTypes()[$type];

                $buffer = Tensor::newBuffer($outputTensorSize, $phpTensorType);

                $stringPtr = FFI::string($arr, FFI::sizeof($arr));

                $buffer->load($stringPtr);

                return new Tensor($buffer, $phpTensorType, $shape, 0);
            } elseif ($outType->cdata == $this->ort->enum('ONNX_TYPE_SEQUENCE')) {
                $out = $this->ort->GetValueCount($outPtr);

                $result = [];
                for ($i = 0; $i < $out; $i++) {
                    $sequence = $this->ort->GetValue($outPtr, $i, $this->allocator);
                    $result[] = $this->ortValueToTensor($sequence);
                }
                return $result;
            } elseif ($outType->cdata == $this->ort->enum('ONNX_TYPE_MAP')) {
                $mapKeys = $this->ort->GetValue($outPtr, 0, $this->allocator);
                $mapValues = $this->ort->GetValue($outPtr, 1, $this->allocator);
                $typeShape = $this->ort->GetTensorTypeAndShape($mapKeys);
                $elemType = $this->ort->GetTensorElementType($typeShape);

                $this->ort->ReleaseTensorTypeAndShapeInfo($typeShape);

                if ($elemType->cdata == $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64')) {
                    $keys = $this->ortValueToTensor($mapKeys);
                    $values = $this->ortValueToTensor($mapValues);

                    $pairs = Tensor::stack([$keys->to($values->dtype()), $values], 1);
                    return $pairs;
                } else {
                    $this->unsupportedType('element', $elemType);
                }
            } else {
                $this->unsupportedType('ONNX', $outType->cdata);
            }
        } finally {
            if (!FFI::isNull($outPtr)) {
                $this->ort->ReleaseValue($outPtr);
            }
        }
    }

    private function ortValueToStrings($outPtr, $outputTensorSize): array
    {
        $len = $this->ort->GetStringTensorDataLength($outPtr);

        [$s, $offsets] = $this->ort->GetStringTensorContent($outPtr, $len, $outputTensorSize);

        $result = [];
        foreach ($offsets as $i => $v) {
            $start = $v;
            $end = $i < $outputTensorSize - 1 ? $offsets[$i + 1] : $len;
            $size = $end - $start;
            $result[] = FFI::string($s + $start, $size);
        }
        return $result;
    }

    private function nodeInfo($typeInfo)
    {
        $onnxType = $this->ort->GetOnnxTypeFromTypeInfo($typeInfo);

        if ($onnxType->cdata == $this->ort->enum('ONNX_TYPE_TENSOR')) {
            // don't free tensor_info
            $tensorInfo = $this->ort->CastTypeInfoToTensorInfo($typeInfo);

            [$type, $shape] = $this->tensorTypeAndShape($tensorInfo);
            $elementDataType = $this->elementDataTypes()[$type];

            return ['type' => "tensor($elementDataType)", 'shape' => $shape];
        } elseif ($onnxType->cdata == $this->ort->enum('ONNX_TYPE_SEQUENCE')) {
            $sequenceTypeInfo = $this->ort->CastTypeInfoToSequenceTypeInfo($typeInfo);
            $nestedTypeInfo = $this->ort->GetSequenceElementType($sequenceTypeInfo);

            $v = $this->nodeInfo($nestedTypeInfo)['type'];

            return ['type' => "seq($v)", 'shape' => []];
        } elseif ($onnxType->cdata == $this->ort->enum('ONNX_TYPE_MAP')) {
            $mapTypeInfo = $this->ort->CastTypeInfoToMapTypeInfo($typeInfo);

            // key
            $keyType = $this->ort->GetMapKeyType($mapTypeInfo);
            $k = $this->elementDataTypes()[$keyType->cdata];

            // value
            $valueTypeInfo = $this->ort->GetMapValueType($mapTypeInfo);
            $v = $this->nodeInfo($valueTypeInfo)['type'];

            return ['type' => "map($k,$v)", 'shape' => []];
        } else {
            $this->unsupportedType('ONNX', $onnxType->cdata);
        }
    }

    private function castTypes(): array
    {
        return [
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT') => 'float',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8') => 'uint8_t',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8') => 'int8_t',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16') => 'uint16_t',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16') => 'int16_t',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32') => 'int32_t',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64') => 'int64_t',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL') => 'bool',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE') => 'double',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32') => 'uint32_t',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64') => 'uint64_t',
        ];
    }

    private function elementDataTypes(): array
    {
        return [
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UNDEFINED') => 'undefined',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT') => 'float',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8') => 'uint8',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8') => 'int8',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16') => 'uint16',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16') => 'int16',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32') => 'int32',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64') => 'int64',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING') => 'string',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL') => 'bool',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT16') => 'float16',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE') => 'double',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32') => 'uint32',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64') => 'uint64',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX64') => 'complex64',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX128') => 'complex128',
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BFLOAT16') => 'bfloat16',
        ];
    }

    private function phpTensorTypes(): array
    {
        return [
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT') => Tensor::float32,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8') => Tensor::uint8,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8') => Tensor::int8,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16') => Tensor::uint16,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16') => Tensor::int16,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32') => Tensor::int32,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64') => Tensor::int64,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL') => Tensor::bool,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE') => Tensor::float64,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32') => Tensor::uint32,
            $this->ort->enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64') => Tensor::uint64,
        ];
    }


    private function tensorTypeAndShape($tensorInfo): array
    {
        $type = $this->ort->GetTensorElementType($tensorInfo);
        $numDims = $this->ort->GetDimensionsCount($tensorInfo);

        if ($numDims > 0) {
            $dims = $this->ort->GetDimensions($tensorInfo, $numDims);
            $symbolicDims = $this->ort->GetSymbolicDimensions($tensorInfo, $numDims);

            for ($i = 0; $i < $numDims; $i++) {
                $namedDim = FFI::string($symbolicDims[$i]);
                if ($namedDim != '') {
                    $dims[$i] = $namedDim;
                }
            }
        } else {
            $dims = [];
        }

        return [$type->cdata, $dims];
    }

    private function unsupportedType($name, $type)
    {
        throw new Exception("Unsupported $name type: $type");
    }

    // wide string on Windows
    // char string on Linux
    // see ORTCHAR_T in onnxruntime_c_api.h
    private function ortString($str)
    {
        if (PHP_OS_FAMILY == 'Windows') {
            $max = strlen($str) + 1; // for null byte

            // wchar_t not supported, so use char instead of casting later
            // since FFI::cast only references data
            $dest = Libc::new('char[' . ($max * 2) . ']');

            Libc::mbStringToWcString($dest, $str, $max);

            return $dest;
        } else {
            return $str;
        }
    }

    private function env()
    {
        // TODO use mutex for thread-safety
        $env = $this->ort->CreateEnv(3, 'Default');

        register_shutdown_function(function () use ($env) {
            $this->ort->ReleaseEnv($env);
        });

        return $env;
    }
}
