<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use OnnxRuntime\Exception;
use OnnxRuntime\FFI;

class InferenceSession
{
    private $ffi;
    private mixed $api;
    private $session;
    private $allocator;
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
        $this->ffi = FFI::instance();
        $this->api = self::api();

        // session options
        $sessionOptions = $this->ffi->new('OrtSessionOptions*');
        $this->checkStatus(($this->api->CreateSessionOptions)(\FFI::addr($sessionOptions)));
        if ($enableCpuMemArena) {
            $this->checkStatus(($this->api->EnableCpuMemArena)($sessionOptions));
        } else {
            $this->checkStatus(($this->api->DisableCpuMemArena)($sessionOptions));
        }
        if ($enableMemPattern) {
            $this->checkStatus(($this->api->EnableMemPattern)($sessionOptions));
        } else {
            $this->checkStatus(($this->api->DisableMemPattern)($sessionOptions));
        }
        if ($enableProfiling) {
            $this->checkStatus(($this->api->EnableProfiling)($sessionOptions, $this->ortString($profileFilePrefix ?? 'onnxruntime_profile_')));
        } else {
            $this->checkStatus(($this->api->DisableProfiling)($sessionOptions));
        }
        if (!is_null($executionMode)) {
            $this->checkStatus(($this->api->SetSessionExecutionMode)($sessionOptions, $executionMode->value));
        }
        if (!is_null($freeDimensionOverridesByDenotation)) {
            foreach ($freeDimensionOverridesByDenotation as $k => $v) {
                $this->checkStatus(($this->api->AddFreeDimensionOverride)($sessionOptions, $k, $v));
            }
        }
        if (!is_null($freeDimensionOverridesByName)) {
            foreach ($freeDimensionOverridesByName as $k => $v) {
                $this->checkStatus(($this->api->AddFreeDimensionOverrideByName)($sessionOptions, $k, $v));
            }
        }
        if (!is_null($graphOptimizationLevel)) {
            $this->checkStatus(($this->api->SetSessionGraphOptimizationLevel)($sessionOptions, $graphOptimizationLevel->value));
        }
        if (!is_null($interOpNumThreads)) {
            $this->checkStatus(($this->api->SetInterOpNumThreads)($sessionOptions, $interOpNumThreads));
        }
        if (!is_null($intraOpNumThreads)) {
            $this->checkStatus(($this->api->SetIntraOpNumThreads)($sessionOptions, $intraOpNumThreads));
        }
        if (!is_null($logSeverityLevel)) {
            $this->checkStatus(($this->api->SetSessionLogSeverityLevel)($sessionOptions, $logSeverityLevel));
        }
        if (!is_null($logVerbosityLevel)) {
            $this->checkStatus(($this->api->SetSessionLogVerbosityLevel)($sessionOptions, $logVerbosityLevel));
        }
        if (!is_null($logid)) {
            $this->checkStatus(($this->api->SetSessionLogId)($sessionOptions, $logid));
        }
        if (!is_null($optimizedModelFilepath)) {
            $this->checkStatus(($this->api->SetOptimizedModelFilePath)($sessionOptions, $this->ortString($optimizedModelFilepath)));
        }
        if (!is_null($sessionConfigEntries)) {
            foreach ($sessionConfigEntries as $k => $v) {
                $this->checkStatus(($this->api->AddSessionConfigEntry)($sessionOptions, $k, $v));
            }
        }
        foreach ($providers as $provider) {
            if (!in_array($provider, $this->providers())) {
                trigger_error('Provider not available: ' . $provider, E_USER_WARNING);
                continue;
            }

            if ($provider == 'CUDAExecutionProvider') {
                $cudaOptions = $this->ffi->new('OrtCUDAProviderOptionsV2*');
                $this->checkStatus(($this->api->CreateCUDAProviderOptions)(\FFI::addr($cudaOptions)));
                $this->checkStatus(($this->api->SessionOptionsAppendExecutionProvider_CUDA_V2)($sessionOptions, $cudaOptions));
                ($this->api->ReleaseCUDAProviderOptions)($cudaOptions);
            } elseif ($provider == 'CPUExecutionProvider') {
                break;
            } else {
                throw new \InvalidArgumentException('Provider not supported: ' . $provider);
            }
        }

        $this->session = $this->loadSession($path, $sessionOptions);
        $this->allocator = $this->loadAllocator();
        $this->inputs = $this->loadInputs();
        $this->outputs = $this->loadOutputs();

        ($this->api->ReleaseSessionOptions)($sessionOptions);
    }

    public function __destruct()
    {
        ($this->api->ReleaseSession)($this->session);
    }

    public function run($outputNames, $inputFeed, $logSeverityLevel = null, $logVerbosityLevel = null, $logid = null, $terminate = null): array
    {
        // pointer references
        $refs = [];

        $inputTensor = $this->createInputTensor($inputFeed, $refs);

        $outputNames ??= array_map(fn($v) => $v['name'], $this->outputs);

        $outputsSize = count($outputNames);
        $outputTensor = $this->ffi->new("OrtValue*[$outputsSize]");
        $inputNodeNames = $this->createNodeNames(array_keys($inputFeed), $refs);
        $outputNodeNames = $this->createNodeNames($outputNames, $refs);

        // run options
        $runOptions = $this->ffi->new('OrtRunOptions*');
        $this->checkStatus(($this->api->CreateRunOptions)(\FFI::addr($runOptions)));
        if (!is_null($logVerbosityLevel)) {
            $this->checkStatus(($this->api->RunOptionsSetRunLogSeverityLevel)($runOptions, $logSeverityLevel));
        }
        if (!is_null($logVerbosityLevel)) {
            $this->checkStatus(($this->api->RunOptionsSetRunLogVerbosityLevel)($runOptions, $logVerbosityLevel));
        }
        if (!is_null($logid)) {
            $this->checkStatus(($this->api->RunOptionsSetRunTag)($runOptions, $logid));
        }
        if (!is_null($terminate)) {
            if ($terminate) {
                $this->checkStatus(($this->api->RunOptionsSetTerminate)($runOptions));
            } else {
                $this->checkStatus(($this->api->RunOptionsUnsetTerminate)($runOptions));
            }
        }

        $this->checkStatus(($this->api->Run)($this->session, $runOptions, $inputNodeNames, $inputTensor, count($inputFeed), $outputNodeNames, count($outputNames), $outputTensor));

        $output = [];

        foreach ($outputTensor as $i => $t) {
//            $output[] = $this->createFromOnnxValue($t);
            $output[$outputNames[$i]] = $this->createFromOnnxValue($t);
        }

        // TODO use finally
        ($this->api->ReleaseRunOptions)($runOptions);
        if ($inputTensor) {
            for ($i = 0; $i < count($inputFeed); $i++) {
                ($this->api->ReleaseValue)($inputTensor[$i]);
            }
        }
        // output values released in createFromOnnxValue

        return $output;
    }

    public function inputs()
    {
        return $this->inputs;
    }

    public function outputs()
    {
        return $this->outputs;
    }

    public function modelmeta()
    {
        $keys = $this->ffi->new('char**');
        $numKeys = $this->ffi->new('int64_t');
        $description = $this->ffi->new('char*');
        $domain = $this->ffi->new('char*');
        $graphName = $this->ffi->new('char*');
        $graphDescription = $this->ffi->new('char*');
        $producerName = $this->ffi->new('char*');
        $version = $this->ffi->new('int64_t');

        $metadata = $this->ffi->new('OrtModelMetadata*');
        $this->checkStatus(($this->api->SessionGetModelMetadata)($this->session, \FFI::addr($metadata)));

        $customMetadataMap = [];
        $this->checkStatus(($this->api->ModelMetadataGetCustomMetadataMapKeys)($metadata, $this->allocator, \FFI::addr($keys), \FFI::addr($numKeys)));
        for ($i = 0; $i < $numKeys->cdata; $i++) {
            $keyPtr = $keys[$i];
            $key = \FFI::string($keyPtr);
            $value = $this->ffi->new('char*');
            $this->checkStatus(($this->api->ModelMetadataLookupCustomMetadataMap)($metadata, $this->allocator, $key, \FFI::addr($value)));
            $customMetadataMap[$key] = \FFI::string($value);

            $this->allocatorFree($keyPtr);
            $this->allocatorFree($value);
        }
        $this->allocatorFree($keys);

        $this->checkStatus(($this->api->ModelMetadataGetDescription)($metadata, $this->allocator, \FFI::addr($description)));
        $this->checkStatus(($this->api->ModelMetadataGetDomain)($metadata, $this->allocator, \FFI::addr($domain)));
        $this->checkStatus(($this->api->ModelMetadataGetGraphName)($metadata, $this->allocator, \FFI::addr($graphName)));
        $this->checkStatus(($this->api->ModelMetadataGetGraphDescription)($metadata, $this->allocator, \FFI::addr($graphDescription)));
        $this->checkStatus(($this->api->ModelMetadataGetProducerName)($metadata, $this->allocator, \FFI::addr($producerName)));
        $this->checkStatus(($this->api->ModelMetadataGetVersion)($metadata, \FFI::addr($version)));

        $ret = [
            'custom_metadata_map' => $customMetadataMap,
            'description' => \FFI::string($description),
            'domain' => \FFI::string($domain),
            'graph_name' => \FFI::string($graphName),
            'graph_description' => \FFI::string($graphDescription),
            'producer_name' => \FFI::string($producerName),
            'version' => $version->cdata
        ];

        // TODO use finally
        ($this->api->ReleaseModelMetadata)($metadata);
        $this->allocatorFree($description);
        $this->allocatorFree($domain);
        $this->allocatorFree($graphName);
        $this->allocatorFree($graphDescription);
        $this->allocatorFree($producerName);

        return $ret;
    }

    // return value has double underscore like Python
    public function endProfiling()
    {
        $out = $this->ffi->new('char*');
        $this->checkStatus(($this->api->SessionEndProfiling)($this->session, $this->allocator, \FFI::addr($out)));
        return \FFI::string($out);
    }

    // no way to set providers with C API yet
    // so we can return all available providers
    public function providers()
    {
        $outPtr = $this->ffi->new('char**');
        $lengthPtr = $this->ffi->new('int');
        $this->checkStatus(($this->api->GetAvailableProviders)(\FFI::addr($outPtr), \FFI::addr($lengthPtr)));
        $length = $lengthPtr->cdata;
        $providers = [];
        for ($i = 0; $i < $length; $i++) {
            $providers[] = \FFI::string($outPtr[$i]);
        }
        ($this->api->ReleaseAvailableProviders)($outPtr, $length);
        return $providers;
    }

    private function loadSession($path, $sessionOptions)
    {
        $session = $this->ffi->new('OrtSession*');
        if (is_resource($path) && get_resource_type($path) == 'stream') {
            $contents = stream_get_contents($path);
            $this->checkStatus(($this->api->CreateSessionFromArray)(self::env(), $contents, strlen($contents), $sessionOptions, \FFI::addr($session)));
        } else {
            $this->checkStatus(($this->api->CreateSession)(self::env(), $this->ortString($path), $sessionOptions, \FFI::addr($session)));
        }
        return $session;
    }

    private function loadAllocator()
    {
        $allocator = $this->ffi->new('OrtAllocator*');
        $this->checkStatus(($this->api->GetAllocatorWithDefaultOptions)(\FFI::addr($allocator)));
        return $allocator;
    }

    private function loadInputs()
    {
        $inputs = [];
        $numInputNodes = $this->ffi->new('size_t');
        $this->checkStatus(($this->api->SessionGetInputCount)($this->session, \FFI::addr($numInputNodes)));
        for ($i = 0; $i < $numInputNodes->cdata; $i++) {
            $namePtr = $this->ffi->new('char*');
            $this->checkStatus(($this->api->SessionGetInputName)($this->session, $i, $this->allocator, \FFI::addr($namePtr)));
            // freed in nodeInfo
            $typeinfo = $this->ffi->new('OrtTypeInfo*');
            $this->checkStatus(($this->api->SessionGetInputTypeInfo)($this->session, $i, \FFI::addr($typeinfo)));
            $inputs[] = array_merge(['name' => \FFI::string($namePtr)], $this->nodeInfo($typeinfo));
            $this->allocatorFree($namePtr);
        }
        return $inputs;
    }

    private function loadOutputs()
    {
        $outputs = [];
        $numOutputNodes = $this->ffi->new('size_t');
        $this->checkStatus(($this->api->SessionGetOutputCount)($this->session, \FFI::addr($numOutputNodes)));
        for ($i = 0; $i < $numOutputNodes->cdata; $i++) {
            $namePtr = $this->ffi->new('char*');
            $this->checkStatus(($this->api->SessionGetOutputName)($this->session, $i, $this->allocator, \FFI::addr($namePtr)));
            // freed in nodeInfo
            $typeinfo = $this->ffi->new('OrtTypeInfo*');
            $this->checkStatus(($this->api->SessionGetOutputTypeInfo)($this->session, $i, \FFI::addr($typeinfo)));
            $outputs[] = array_merge(['name' => \FFI::string($namePtr)], $this->nodeInfo($typeinfo));
            $this->allocatorFree($namePtr);
        }
        return $outputs;
    }

    private function createInputTensor($inputFeed, &$refs)
    {
        $allocatorInfo = $this->ffi->new('OrtMemoryInfo*');
        $this->checkStatus(($this->api->CreateCpuMemoryInfo)(1, 0, \FFI::addr($allocatorInfo)));
        $inputFeedSize = count($inputFeed);
        if ($inputFeedSize == 0) {
            throw new Exception('No input');
        }
        $inputTensor = $this->ffi->new("OrtValue*[$inputFeedSize]");

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
            $inputNodeDims = $this->ffi->new("int64_t[$ndim]");
            for ($i = 0; $i < $ndim; $i++) {
                $inputNodeDims[$i] = $shape[$i];
            }

            if ($inp['type'] == 'tensor(string)') {
                $size = $input->size();
                $inputTensorValues = $this->ffi->new("char*[$size]");
                $this->fillStringTensorValues($input, $inputTensorValues, $shape, $refs);

                $typeEnum = $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING;
                $this->checkStatus(($this->api->CreateTensorAsOrtValue)($this->allocator, $inputNodeDims, $ndim, $typeEnum, \FFI::addr($inputTensor[$idx])));
                $this->checkStatus(($this->api->FillStringTensor)($inputTensor[$idx], $inputTensorValues, count($inputTensorValues)));
            } else {
                $size = $input->size();

                $inputTypes = array_flip(array_map(fn($v) => "tensor($v)", $this->elementDataTypes()));
                if (isset($inputTypes[$inp['type']])) {
                    $typeEnum = $inputTypes[$inp['type']];
                    $castType = $this->castTypes()[$typeEnum];
                    $inputTensorValues = $this->ffi->new("{$castType}[$size]");
                } else {
                    $this->unsupportedType('input', $inp['type']);
                }

                $this->fillTensorValues($input, $inputTensorValues);

                $this->checkStatus(($this->api->CreateTensorWithDataAsOrtValue)($allocatorInfo, $inputTensorValues, \FFI::sizeof($inputTensorValues), $inputNodeDims, $ndim, $typeEnum, \FFI::addr($inputTensor[$idx])));

                $refs[] = $inputNodeDims;
                $refs[] = $inputTensorValues;
            }
            $idx++;
        }

        // TODO use finally
        ($this->api->ReleaseMemoryInfo)($allocatorInfo);

        return $inputTensor;
    }

    private function fillStringTensorValues(Tensor $input, $ptr, &$refs): void
    {
        foreach ($input->buffer() as $i => $v) {
            $strPtr = $this->cstring($v);
            $ptr[$i] = $strPtr;
            $refs[] = $strPtr;
        }
    }

    private function fillTensorValues(Tensor $input, $ptr): void
    {
        foreach ($input->buffer() as $i => $v) {
            $ptr[$i] = $v;
        }
    }

    private function createNodeNames($names, &$refs)
    {
        $namesSize = count($names);
        $ptr = $this->ffi->new("char*[$namesSize]");
        foreach ($names as $i => $name) {
            $strPtr = $this->cstring($name);
            $ptr[$i] = $strPtr;
            $refs[] = $strPtr;
        }
        return $ptr;
    }

    private function cstring($str)
    {
        $bytes = strlen($str) + 1;
        // TODO fix?
        $ptr = $this->ffi->new("char[$bytes]", owned: false);
        \FFI::memcpy($ptr, $str, $bytes - 1);
        $ptr[$bytes - 1] = "\0";
        return $ptr;
    }

    private function createFromOnnxValue($outPtr)
    {
        try {
            $outType = $this->ffi->new('ONNXType');
            $this->checkStatus(($this->api->GetValueType)($outPtr, \FFI::addr($outType)));

            if ($outType->cdata == $this->ffi->ONNX_TYPE_TENSOR) {
                $typeinfo = $this->ffi->new('OrtTensorTypeAndShapeInfo*');
                $this->checkStatus(($this->api->GetTensorTypeAndShape)($outPtr, \FFI::addr($typeinfo)));

                [$type, $shape] = $this->tensorTypeAndShape($typeinfo);

                // TODO skip if string
                $tensorData = $this->ffi->new('void*');
                $this->checkStatus(($this->api->GetTensorMutableData)($outPtr, \FFI::addr($tensorData)));

                $outSize = $this->ffi->new('size_t');
                $this->checkStatus(($this->api->GetTensorShapeElementCount)($typeinfo, \FFI::addr($outSize)));
                $outputTensorSize = $outSize->cdata;

                ($this->api->ReleaseTensorTypeAndShapeInfo)($typeinfo);

                $castTypes = $this->castTypes();
                if (isset($castTypes[$type])) {
                    $arr = $this->ffi->cast($castTypes[$type] . "[$outputTensorSize]", $tensorData);
                } elseif ($type == $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING) {
                    $arr = $this->createStringsFromOnnxValue($outPtr, $outputTensorSize);
                } else {
                    $this->unsupportedType('element', $type);
                }

                return $this->fillOutput($arr, $shape);
            } elseif ($outType->cdata == $this->ffi->ONNX_TYPE_SEQUENCE) {
                $out = $this->ffi->new('size_t');
                $this->checkStatus(($this->api->GetValueCount)($outPtr, \FFI::addr($out)));

                $ret = [];
                for ($i = 0; $i < $out->cdata; $i++) {
                    $seq = $this->ffi->new('OrtValue*');
                    $this->checkStatus(($this->api->GetValue)($outPtr, $i, $this->allocator, \FFI::addr($seq)));
                    $ret[] = $this->createFromOnnxValue($seq);
                }
                return $ret;
            } elseif ($outType->cdata == $this->ffi->ONNX_TYPE_MAP) {
                $typeShape = $this->ffi->new('OrtTensorTypeAndShapeInfo*');
                $mapKeys = $this->ffi->new('OrtValue*');
                $mapValues = $this->ffi->new('OrtValue*');
                $elemType = $this->ffi->new('ONNXTensorElementDataType');

                $this->checkStatus(($this->api->GetValue)($outPtr, 0, $this->allocator, \FFI::addr($mapKeys)));
                $this->checkStatus(($this->api->GetValue)($outPtr, 1, $this->allocator, \FFI::addr($mapValues)));
                $this->checkStatus(($this->api->GetTensorTypeAndShape)($mapKeys, \FFI::addr($typeShape)));
                $this->checkStatus(($this->api->GetTensorElementType)($typeShape, \FFI::addr($elemType)));

                ($this->api->ReleaseTensorTypeAndShapeInfo)($typeShape);

                // TODO support more types
                if ($elemType->cdata == $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64) {
                    $ret = [];
                    $keys = $this->createFromOnnxValue($mapKeys);
                    $values = $this->createFromOnnxValue($mapValues);
                    return array_combine($keys, $values);
                } else {
                    $this->unsupported_type('element', $elemType);
                }
            } else {
                $this->unsupportedType('ONNX', $outType->cdata);
            }
        } finally {
            if (!\FFI::isNull($outPtr)) {
                ($this->api->ReleaseValue)($outPtr);
            }
        }
    }

    private function fillOutput($ptr, $shape): ?Tensor
    {
        $bufferSize = count($ptr);

        if ($bufferSize == 0) {
            return null;
        }

        $data = [];

        for ($j = 0; $j < $bufferSize; $j++) {
            $data[] = $ptr[$j];
        }

        return new Tensor($data, shape: $shape);
    }

    private function createStringsFromOnnxValue($outPtr, $outputTensorSize)
    {
        $len = $this->ffi->new('size_t');
        $this->checkStatus(($this->api->GetStringTensorDataLength)($outPtr, \FFI::addr($len)));

        $sLen = $len->cdata;
        $s = $this->ffi->new("char[$sLen]");
        $offsets = $this->ffi->new("size_t[$outputTensorSize]");
        $this->checkStatus(($this->api->GetStringTensorContent)($outPtr, $s, $sLen, $offsets, $outputTensorSize));

        $result = [];
        foreach ($offsets as $i => $v) {
            $start = $v;
            $end = $i < $outputTensorSize - 1 ? $offsets[$i + 1] : $sLen;
            $size = $end - $start;
            $result[] = \FFI::string($s + $start, $size);
        }
        return $result;
    }

    private static function checkStatus($status)
    {
        if (!is_null($status)) {
            $message = (self::api()->GetErrorMessage)($status);
            (self::api()->ReleaseStatus)($status);
            throw new Exception($message);
        }
    }

    private function nodeInfo($typeinfo)
    {
        $onnxType = $this->ffi->new('ONNXType');
        $this->checkStatus(($this->api->GetOnnxTypeFromTypeInfo)($typeinfo, \FFI::addr($onnxType)));

        if ($onnxType->cdata == $this->ffi->ONNX_TYPE_TENSOR) {
            $tensorInfo = $this->ffi->new('OrtTensorTypeAndShapeInfo*');
            // don't free tensor_info
            $this->checkStatus(($this->api->CastTypeInfoToTensorInfo)($typeinfo, \FFI::addr($tensorInfo)));

            [$type, $shape] = $this->tensorTypeAndShape($tensorInfo);
            $elementDataType = $this->elementDataTypes()[$type];
            return ['type' => "tensor($elementDataType)", 'shape' => $shape];
        } elseif ($onnxType->cdata == $this->ffi->ONNX_TYPE_SEQUENCE) {
            $sequenceTypeInfo = $this->ffi->new('OrtSequenceTypeInfo*');
            $this->checkStatus(($this->api->CastTypeInfoToSequenceTypeInfo)($typeinfo, \FFI::addr($sequenceTypeInfo)));
            $nestedTypeInfo = $this->ffi->new('OrtTypeInfo*');
            $this->checkStatus(($this->api->GetSequenceElementType)($sequenceTypeInfo, \FFI::addr($nestedTypeInfo)));
            $v = $this->nodeInfo($nestedTypeInfo)['type'];

            return ['type' => "seq($v)", 'shape' => []];
        } elseif ($onnxType->cdata == $this->ffi->ONNX_TYPE_MAP) {
            $mapTypeInfo = $this->ffi->new('OrtMapTypeInfo*');
            $this->checkStatus(($this->api->CastTypeInfoToMapTypeInfo)($typeinfo, \FFI::addr($mapTypeInfo)));

            // key
            $keyType = $this->ffi->new('ONNXTensorElementDataType');
            $this->checkStatus(($this->api->GetMapKeyType)($mapTypeInfo, \FFI::addr($keyType)));
            $k = $this->elementDataTypes()[$keyType->cdata];

            // value
            $valueTypeInfo = $this->ffi->new('OrtTypeInfo*');
            $this->checkStatus(($this->api->GetMapValueType)($mapTypeInfo, \FFI::addr($valueTypeInfo)));
            $v = $this->nodeInfo($valueTypeInfo)['type'];

            return ['type' => "map($k,$v)", 'shape' => []];
        } else {
            $this->unsupportedType('ONNX', $onnxType->cdata);
        }
    }

    private function castTypes()
    {
        return [
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT => 'float',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8 => 'uint8_t',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8 => 'int8_t',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16 => 'uint16_t',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16 => 'int16_t',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32 => 'int32_t',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64 => 'int64_t',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL => 'bool',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE => 'double',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32 => 'uint32_t',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64 => 'uint64_t'
        ];
    }

    private function elementDataTypes()
    {
        return [
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UNDEFINED => 'undefined',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT => 'float',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8 => 'uint8',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8 => 'int8',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16 => 'uint16',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16 => 'int16',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32 => 'uint32',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64 => 'int64',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING => 'string',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL => 'bool',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT16 => 'float16',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE => 'double',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32 => 'uint32',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64 => 'uint64',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX64 => 'complex64',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX128 => 'complex128',
            $this->ffi->ONNX_TENSOR_ELEMENT_DATA_TYPE_BFLOAT16 => 'bfloat16'
        ];
    }

    private function tensorTypeAndShape($tensorInfo)
    {
        $type = $this->ffi->new('ONNXTensorElementDataType');
        $this->checkStatus(($this->api->GetTensorElementType)($tensorInfo, \FFI::addr($type)));

        $numDimsPtr = $this->ffi->new('size_t');
        $this->checkStatus(($this->api->GetDimensionsCount)($tensorInfo, \FFI::addr($numDimsPtr)));
        $numDims = $numDimsPtr->cdata;

        if ($numDims > 0) {
            $nodeDims = $this->ffi->new("int64_t[$numDims]");
            $this->checkStatus(($this->api->GetDimensions)($tensorInfo, $nodeDims, $numDims));
            $dims = $this->readArray($nodeDims);

            $symbolicDims = $this->ffi->new("char*[$numDims]");
            $this->checkStatus(($this->api->GetSymbolicDimensions)($tensorInfo, $symbolicDims, $numDims));
            for ($i = 0; $i < $numDims; $i++) {
                $namedDim = \FFI::string($symbolicDims[$i]);
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

    private function readArray($cdata)
    {
        $arr = [];
        $n = count($cdata);
        for ($i = 0; $i < $n; $i++) {
            $arr[] = $cdata[$i];
        }
        return $arr;
    }

    private function allocatorFree($ptr)
    {
        ($this->api->AllocatorFree)($this->allocator, $ptr);
    }

    private static function api()
    {
        return (FFI::instance()->OrtGetApiBase()[0]->GetApi)(11)[0];
    }

    // wide string on Windows
    // char string on Linux
    // see ORTCHAR_T in onnxruntime_c_api.h
    private function ortString($str)
    {
        if (PHP_OS_FAMILY == 'Windows') {
            $libc = FFI::libc();
            $max = strlen($str) + 1; // for null byte
            // wchar_t not supported
            // use char instead of casting later
            // since FFI::cast only references data
            $dest = $libc->new('char[' . ($max * 2) . ']');
            $ret = $libc->mbstowcs($dest, $str, $max);
            if ($ret != strlen($str)) {
                throw new Exception('Expected mbstowcs to return ' . strlen($str) . ", got $ret");
            }
            return $dest;
        } else {
            return $str;
        }
    }

    private static function env()
    {
        // TODO use mutex for thread-safety
        // TODO memoize

        $env = FFI::instance()->new('OrtEnv*');
        (self::api()->CreateEnv)(3, 'Default', \FFI::addr($env));
        // disable telemetry
        // https://github.com/microsoft/onnxruntime/blob/master/docs/Privacy.md
        self::checkStatus((self::api()->DisableTelemetryEvents)($env));
        return $env;
    }
}