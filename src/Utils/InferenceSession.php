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

class InferenceSession
{
    private ?CData $session;
    private ?CData $allocator;
    private array $inputs;
    private array $outputs;

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
    )
    {
//        $providers = ['CoreMLExecutionProvider', 'CPUExecutionProvider'];
        // session options
        $sessionOptions = OnnxRuntime::CreateSessionOptions();

        if ($enableCpuMemArena) {
            OnnxRuntime::EnableCpuMemArena($sessionOptions);
        } else {
            OnnxRuntime::DisableCpuMemArena($sessionOptions);
        }
        if ($enableMemPattern) {
            OnnxRuntime::EnableMemPattern($sessionOptions);
        } else {
            OnnxRuntime::DisableMemPattern($sessionOptions);
        }
        if ($enableProfiling) {
            OnnxRuntime::EnableProfiling($sessionOptions, $this->ortString($profileFilePrefix ?? 'onnxruntime_profile_'));
        } else {
            OnnxRuntime::DisableProfiling($sessionOptions);
        }
        if (!is_null($executionMode)) {
            OnnxRuntime::SetSessionExecutionMode($sessionOptions, $executionMode->value);
        }
        if (!is_null($freeDimensionOverridesByDenotation)) {
            foreach ($freeDimensionOverridesByDenotation as $k => $v) {
                OnnxRuntime::AddFreeDimensionOverride($sessionOptions, $k, $v);
            }
        }
        if (!is_null($freeDimensionOverridesByName)) {
            foreach ($freeDimensionOverridesByName as $k => $v) {
                OnnxRuntime::AddFreeDimensionOverrideByName($sessionOptions, $k, $v);
            }
        }
        if (!is_null($graphOptimizationLevel)) {
            OnnxRuntime::SetSessionGraphOptimizationLevel($sessionOptions, $graphOptimizationLevel->value);
        }
        if (!is_null($interOpNumThreads)) {
            OnnxRuntime::SetInterOpNumThreads($sessionOptions, $interOpNumThreads);
        }
        if (!is_null($intraOpNumThreads)) {
            OnnxRuntime::SetIntraOpNumThreads($sessionOptions, $intraOpNumThreads);
        }
        if (!is_null($logSeverityLevel)) {
            OnnxRuntime::SetSessionLogSeverityLevel($sessionOptions, $logSeverityLevel);
        }
        if (!is_null($logVerbosityLevel)) {
            OnnxRuntime::SetSessionLogVerbosityLevel($sessionOptions, $logVerbosityLevel);
        }
        if (!is_null($logid)) {
            OnnxRuntime::SetSessionLogId($sessionOptions, $logid);
        }
        if (!is_null($optimizedModelFilepath)) {
            OnnxRuntime::SetOptimizedModelFilePath($sessionOptions, $this->ortString($optimizedModelFilepath));
        }
        if (!is_null($sessionConfigEntries)) {
            foreach ($sessionConfigEntries as $k => $v) {
                OnnxRuntime::AddSessionConfigEntry($sessionOptions, $k, $v);
            }
        }

        foreach ($providers as $provider) {
            if (!in_array($provider, $this->providers())) {
                trigger_error('Provider not available: ' . $provider, E_USER_WARNING);
                continue;
            }

            if ($provider == 'CUDAExecutionProvider') {
                $cudaOptions = OnnxRuntime::CreateCUDAProviderOptions();
                OnnxRuntime::SessionOptionsAppendExecutionProvider_CUDA_V2($sessionOptions, $cudaOptions);
                OnnxRuntime::ReleaseCUDAProviderOptions($cudaOptions);
            } elseif ($provider == 'CoreMLExecutionProvider') {
                OnnxRuntime::OrtSessionOptionsAppendExecutionProvider_CoreML($sessionOptions, 1);
            } elseif ($provider == 'CPUExecutionProvider') {
                break;
            } else {
                throw new \InvalidArgumentException('Provider not supported: ' . $provider);
            }
        }
        $this->session = $this->loadSession($path, $sessionOptions);
        $this->allocator = OnnxRuntime::GetAllocatorWithDefaultOptions();
        $this->inputs = $this->loadInputs();
        $this->outputs = $this->loadOutputs();

        OnnxRuntime::ReleaseSessionOptions($sessionOptions);
    }

    public function __destruct()
    {
        OnnxRuntime::ReleaseSession($this->session);
    }

    public function run($outputNames, $inputFeed, $logSeverityLevel = null, $logVerbosityLevel = null, $logid = null, $terminate = null): array
    {
        // pointer references
        $refs = [];

        $inputTensor = $this->convertInputTensorToOnnxTensor($inputFeed, $refs);
        $outputNames ??= array_map(fn($v) => $v['name'], $this->outputs);

        $inputNodeNames = $this->createNodeNames(array_keys($inputFeed), $refs);
        $outputNodeNames = $this->createNodeNames($outputNames, $refs);

        // run options
        $runOptions = OnnxRuntime::CreateRunOptions();

        if (!is_null($logVerbosityLevel)) {
            OnnxRuntime::RunOptionsSetRunLogSeverityLevel($runOptions, $logSeverityLevel);
        }
        if (!is_null($logVerbosityLevel)) {
            OnnxRuntime::RunOptionsSetRunLogVerbosityLevel($runOptions, $logVerbosityLevel);
        }
        if (!is_null($logid)) {
            OnnxRuntime::RunOptionsSetRunTag($runOptions, $logid);
        }
        if (!is_null($terminate)) {
            if ($terminate) {
                OnnxRuntime::RunOptionsSetTerminate($runOptions);
            } else {
                OnnxRuntime::RunOptionsUnsetTerminate($runOptions);
            }
        }

        $outputTensor = OnnxRuntime::Run($this->session, $runOptions, $inputNodeNames, $inputTensor, count($inputFeed), $outputNodeNames, count($outputNames));

        $output = [];
        foreach ($outputTensor as $i => $t) {
            $output[$outputNames[$i]] = $this->createFromOnnxValue($t);
        }

        // TODO use finally
        OnnxRuntime::ReleaseRunOptions($runOptions);

        if ($inputTensor) {
            for ($i = 0; $i < count($inputFeed); $i++) {
                OnnxRuntime::ReleaseValue($inputTensor[$i]);
            }
        }

        // output values released in createFromOnnxValue

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
        $metadata = OnnxRuntime::SessionGetModelMetadata($this->session);

        $customMetadataMap = [];
        [$keys, $numKeys] = OnnxRuntime::ModelMetadataGetCustomMetadataMapKeys($metadata, $this->allocator);

        for ($i = 0; $i < $numKeys; $i++) {
            $key = $keys[$i];
            $value = OnnxRuntime::ModelMetadataLookupCustomMetadataMap($metadata, $this->allocator, $key);
            $customMetadataMap[$key] = $value;
        }

        $description = OnnxRuntime::ModelMetadataGetDescription($metadata, $this->allocator);
        $domain = OnnxRuntime::ModelMetadataGetDomain($metadata, $this->allocator);
        $graphName = OnnxRuntime::ModelMetadataGetGraphName($metadata, $this->allocator);
//        $graphDescription = OnnxRuntime::ModelMetadataGetGraphDescription($metadata, $this->allocator);
        $producerName = OnnxRuntime::ModelMetadataGetProducerName($metadata, $this->allocator);
        $version = OnnxRuntime::ModelMetadataGetVersion($metadata);

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
        OnnxRuntime::ReleaseModelMetadata($metadata);

        return $result;
    }

    // return value has double underscore like Python
    public function endProfiling(): string
    {
        return OnnxRuntime::SessionEndProfiling($this->session, $this->allocator);
    }

    // No way to set providers with C API yet, so we can return all available providers
    public function providers(): array
    {
        [$outPtr, $length] = OnnxRuntime::GetAvailableProviders();

        $providers = [];

        for ($i = 0; $i < $length; $i++) {
            $providers[] = FFI::string($outPtr[$i]);
        }

        OnnxRuntime::ReleaseAvailableProviders($outPtr, $length);

        return $providers;
    }

    private function loadSession($path, $sessionOptions): ?CData
    {
        if (is_resource($path) && get_resource_type($path) == 'stream') {
            $contents = stream_get_contents($path);
            $session = OnnxRuntime::CreateSessionFromArray(self::env(), $contents, strlen($contents), $sessionOptions);
        } else {
            $session = OnnxRuntime::CreateSession(self::env(), $this->ortString($path), $sessionOptions);
        }
        return $session;
    }

    private function loadInputs(): array
    {
        $inputs = [];
        $numInputNodes = OnnxRuntime::SessionGetInputCount($this->session);

        for ($i = 0; $i < $numInputNodes; $i++) {
            $name = OnnxRuntime::SessionGetInputName($this->session, $i, $this->allocator);

            $typeInfo = OnnxRuntime::SessionGetInputTypeInfo($this->session, $i); // freed in nodeInfo

            $inputs[] = array_merge(['name' => $name], $this->nodeInfo($typeInfo));
        }

        return $inputs;
    }

    private function loadOutputs(): array
    {
        $outputs = [];
        $numOutputNodes = OnnxRuntime::SessionGetOutputCount($this->session);

        for ($i = 0; $i < $numOutputNodes; $i++) {
            $name = OnnxRuntime::SessionGetOutputName($this->session, $i, $this->allocator);

            $typeInfo = OnnxRuntime::SessionGetOutputTypeInfo($this->session, $i); // freed in nodeInfo

            $outputs[] = array_merge(['name' => $name], $this->nodeInfo($typeInfo));
        }

        return $outputs;
    }

    private function convertInputTensorToOnnxTensor($inputFeed, &$refs): ?CData
    {
        $allocatorInfo = OnnxRuntime::CreateCpuMemoryInfo(1, 0);

        $inputFeedSize = count($inputFeed);
        if ($inputFeedSize == 0) {
            throw new Exception('No input');
        }

        $inputTensor = OnnxRuntime::new("OrtValue*[$inputFeedSize]");

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

            $inputNodeShape = OnnxRuntime::new("int64_t[$ndim]");
            for ($i = 0; $i < $ndim; $i++) {
                $inputNodeShape[$i] = $shape[$i];
            }

            if ($inp['type'] == 'tensor(string)') {
                $inputTensorValues = OnnxRuntime::new("char*[$size]");
                $this->fillStringTensorValues($input, $inputTensorValues, $refs);

                $typeEnum = OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING');
                OnnxRuntime::CreateTensorAsOrtValue($this->allocator, $inputNodeShape, $ndim, $typeEnum, FFI::addr($inputTensor[$idx]));
                OnnxRuntime::FillStringTensor($inputTensor[$idx], $inputTensorValues, $size);
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
                    $inputTensorValues = OnnxRuntime::new("void *");
                } else {
                    $inputTensorValues = OnnxRuntime::new("{$castType}[$size]");
                }

                $inputDump = $input->buffer()->dump();
                FFI::memcpy($inputTensorValues, $input->buffer()->dump(), strlen($inputDump));

                OnnxRuntime::CreateTensorWithDataAsOrtValue($allocatorInfo, $inputTensorValues, FFI::sizeof($inputTensorValues), $inputNodeShape, $ndim, $typeEnum, FFI::addr($inputTensor[$idx]));
            }

            $refs[] = $inputNodeShape;
            $refs[] = $inputTensorValues;

            $idx++;
        }

        // TODO use finally
        OnnxRuntime::ReleaseMemoryInfo($allocatorInfo);

        return $inputTensor;
    }

    private function fillStringTensorValues(Tensor $input, $ptr, &$refs): void
    {
        foreach ($input->buffer() as $i => $v) {
            $strPtr = Libc::cstring($v);
            $ptr[$i] = $strPtr;
            $refs[] = $strPtr;
        }
    }

    private function createNodeNames($names, &$refs): CData
    {
        $namesSize = count($names);
        $ptr = OnnxRuntime::new("char*[$namesSize]");
        foreach ($names as $i => $name) {
            $strPtr = Libc::cstring($name);
            $ptr[$i] = $strPtr;
            $refs[] = $strPtr;
        }
        return $ptr;
    }

    private function createFromOnnxValue($outPtr)
    {
        try {
            $outType = OnnxRuntime::GetValueType($outPtr);

            if ($outType->cdata == OnnxRuntime::enum('ONNX_TYPE_TENSOR')) {
                $typeInfo = OnnxRuntime::GetTensorTypeAndShape($outPtr);

                [$type, $shape] = $this->tensorTypeAndShape($typeInfo);

                // TODO skip if string
                $tensorData = OnnxRuntime::GetTensorMutableData($outPtr);

                $outputTensorSize = OnnxRuntime::GetTensorShapeElementCount($typeInfo);

                OnnxRuntime::ReleaseTensorTypeAndShapeInfo($typeInfo);

                $castTypes = $this->castTypes();

                if (isset($castTypes[$type])) {
                    $arr = OnnxRuntime::cast($castTypes[$type] . "[$outputTensorSize]", $tensorData);
                } elseif ($type == OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING')) {
                    $arr = $this->createStringsFromOnnxValue($outPtr, $outputTensorSize);
                } else {
                    $this->unsupportedType('element', $type);
                }

                $phpTensorType = $this->phpTensorTypes()[$type];

                $buffer = Tensor::newBuffer($outputTensorSize, $phpTensorType);

                $stringPtr = FFI::string($arr, FFI::sizeof($arr));

                $buffer->load($stringPtr);

                return new Tensor($buffer, $phpTensorType, $shape, 0);
            } elseif ($outType->cdata == OnnxRuntime::enum('ONNX_TYPE_SEQUENCE')) {
                $out = OnnxRuntime::GetValueCount($outPtr);

                $result = [];
                for ($i = 0; $i < $out; $i++) {
                    $sequence = OnnxRuntime::GetValue($outPtr, $i, $this->allocator);
                    $result[] = $this->createFromOnnxValue($sequence);
                }
                return $result;
            } elseif ($outType->cdata == OnnxRuntime::enum('ONNX_TYPE_MAP')) {
                $mapKeys = OnnxRuntime::GetValue($outPtr, 0, $this->allocator);
                $mapValues = OnnxRuntime::GetValue($outPtr, 1, $this->allocator);
                $typeShape = OnnxRuntime::GetTensorTypeAndShape($mapKeys);
                $elemType = OnnxRuntime::GetTensorElementType($typeShape);

                OnnxRuntime::ReleaseTensorTypeAndShapeInfo($typeShape);

                // TODO support more types
                if ($elemType->cdata == OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64')) {
                    $keys = $this->createFromOnnxValue($mapKeys);
                    $values = $this->createFromOnnxValue($mapValues);
                    return array_combine($keys, $values);
                } else {
                    $this->unsupportedType('element', $elemType);
                }
            } else {
                $this->unsupportedType('ONNX', $outType->cdata);
            }
        } finally {
            if (!FFI::isNull($outPtr)) {
                OnnxRuntime::ReleaseValue($outPtr);
            }
        }
    }

    private function createStringsFromOnnxValue($outPtr, $outputTensorSize): array
    {
        $len = OnnxRuntime::GetStringTensorDataLength($outPtr);

        [$s, $offsets] = OnnxRuntime::GetStringTensorContent($outPtr, $len, $outputTensorSize);

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
        $onnxType = OnnxRuntime::GetOnnxTypeFromTypeInfo($typeInfo);

        if ($onnxType->cdata == OnnxRuntime::enum('ONNX_TYPE_TENSOR')) {
            // don't free tensor_info
            $tensorInfo = OnnxRuntime::CastTypeInfoToTensorInfo($typeInfo);

            [$type, $shape] = $this->tensorTypeAndShape($tensorInfo);
            $elementDataType = $this->elementDataTypes()[$type];

            return ['type' => "tensor($elementDataType)", 'shape' => $shape];
        } elseif ($onnxType->cdata == OnnxRuntime::enum('ONNX_TYPE_SEQUENCE')) {
            $sequenceTypeInfo = OnnxRuntime::CastTypeInfoToSequenceTypeInfo($typeInfo);
            $nestedTypeInfo = OnnxRuntime::GetSequenceElementType($sequenceTypeInfo);

            $v = $this->nodeInfo($nestedTypeInfo)['type'];

            return ['type' => "seq($v)", 'shape' => []];
        } elseif ($onnxType->cdata == OnnxRuntime::enum('ONNX_TYPE_MAP')) {
            $mapTypeInfo = OnnxRuntime::CastTypeInfoToMapTypeInfo($typeInfo);

            // key
            $keyType = OnnxRuntime::GetMapKeyType($mapTypeInfo);
            $k = $this->elementDataTypes()[$keyType->cdata];

            // value
            $valueTypeInfo = OnnxRuntime::GetMapValueType($mapTypeInfo);
            $v = $this->nodeInfo($valueTypeInfo)['type'];

            return ['type' => "map($k,$v)", 'shape' => []];
        } else {
            $this->unsupportedType('ONNX', $onnxType->cdata);
        }
    }

    private function castTypes(): array
    {
        return [
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT') => 'float',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8') => 'uint8_t',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8') => 'int8_t',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16') => 'uint16_t',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16') => 'int16_t',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32') => 'int32_t',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64') => 'int64_t',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL') => 'bool',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE') => 'double',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32') => 'uint32_t',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64') => 'uint64_t',
        ];
    }

    private function elementDataTypes(): array
    {
        return [
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UNDEFINED') => 'undefined',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT') => 'float',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8') => 'uint8',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8') => 'int8',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16') => 'uint16',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16') => 'int16',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32') => 'int32',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64') => 'int64',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING') => 'string',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL') => 'bool',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT16') => 'float16',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE') => 'double',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32') => 'uint32',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64') => 'uint64',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX64') => 'complex64',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX128') => 'complex128',
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BFLOAT16') => 'bfloat16',
        ];
    }

    private function phpTensorTypes(): array
    {
        return [
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT') => Tensor::float32,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8') => Tensor::uint8,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8') => Tensor::int8,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16') => Tensor::uint16,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16') => Tensor::int16,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32') => Tensor::int32,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64') => Tensor::int64,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL') => Tensor::bool,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE') => Tensor::float64,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32') => Tensor::uint32,
            OnnxRuntime::enum('ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64') => Tensor::uint64,
        ];
    }


    private function tensorTypeAndShape($tensorInfo): array
    {
        $type = OnnxRuntime::GetTensorElementType($tensorInfo);
        $numDims = OnnxRuntime::GetDimensionsCount($tensorInfo);

        if ($numDims > 0) {
            $dims = OnnxRuntime::GetDimensions($tensorInfo, $numDims);
            $symbolicDims = OnnxRuntime::GetSymbolicDimensions($tensorInfo, $numDims);

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

    private static function env()
    {
        // TODO use mutex for thread-safety
        // TODO memoize
        return OnnxRuntime::CreateEnv(3, 'Default');
    }
}
