<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

use FFI;
use FFI\CData;
use RuntimeException;

class OnnxRuntime extends NativeLibrary
{
    protected mixed $api;

    public function __construct()
    {
        parent::__construct();
        $this->initApi();
    }


    protected function getHeaderName(): string
    {
        return 'onnxruntime';
    }


    protected function getLibraryName(): string
    {
        return 'onnxruntime';
    }

    /**
     * Get the library version string for this library
     * 
     * @return string The library version
     */
    protected function getLibraryVersion(): string
    {
        return '1.21.0';
    }

    /**
     * Initialize the API
     * 
     * @throws RuntimeException If the API cannot be initialized
     */
    protected function initApi(): void
    {
        $apiBase = $this->ffi->{'OrtGetApiBase'}()[0];
        $this->api = ($apiBase->GetApi)(11)[0];
    }

    /**
     * Returns the version of the library as a string.
     *
     * @return string The version of the library.
     */
    public function version(): string
    {
        $apiBase = $this->ffi->{'OrtGetApiBase'}()[0];
        return ($apiBase->GetVersionString)();
    }

    private function checkStatus($status): void
    {
        if (!is_null($status)) {
            $message = (($this->api)->GetErrorMessage)($status);
            (($this->api)->ReleaseStatus)($status);
            throw new RuntimeException($message);
        }
    }

    public function CreateSession($env, $modelPath, $options): CData
    {
        $session = $this->new('OrtSession*');

        $this->checkStatus((($this->api)->CreateSession)($env, $modelPath, $options, FFI::addr($session)));

        return $session;
    }

    public function CreateSessionFromArray($env, $modelData, $modelDataLength, $options): CData
    {
        $session = $this->new('OrtSession*');

        $this->checkStatus((($this->api)->CreateSessionFromArray)($env, $modelData, $modelDataLength, $options, FFI::addr($session)));

        return $session;
    }

    public function ReleaseSession($session): void
    {
        (($this->api)->ReleaseSession)($session);
    }

    public function CreateSessionOptions(): CData
    {
        $sessionOptions = $this->new('OrtSessionOptions*');

        $this->checkStatus((($this->api)->CreateSessionOptions)(FFI::addr($sessionOptions)));

        return $sessionOptions;
    }

    public function EnableCpuMemArena($sessionOptions): void
    {
        $this->checkStatus((($this->api)->EnableCpuMemArena)($sessionOptions));
    }

    public function DisableCpuMemArena($sessionOptions): void
    {
        $this->checkStatus((($this->api)->DisableCpuMemArena)($sessionOptions));
    }

    public function EnableMemPattern($sessionOptions): void
    {
        $this->checkStatus((($this->api)->EnableMemPattern)($sessionOptions));
    }

    public function DisableMemPattern($sessionOptions): void
    {
        $this->checkStatus((($this->api)->DisableMemPattern)($sessionOptions));
    }

    public function EnableProfiling($sessionOptions, $profileFilePrefix): void
    {
        $this->checkStatus((($this->api)->EnableProfiling)($sessionOptions, $profileFilePrefix));
    }

    public function DisableProfiling($sessionOptions): void
    {
        $this->checkStatus((($this->api)->DisableProfiling)($sessionOptions));
    }

    public function SetSessionExecutionMode($sessionOptions, $executionMode): void
    {
        $this->checkStatus((($this->api)->SetSessionExecutionMode)($sessionOptions, $executionMode));
    }

    public function AddFreeDimensionOverride($sessionOptions, $dimDenotation, int $dimValue): void
    {
        $this->checkStatus((($this->api)->AddFreeDimensionOverride)($sessionOptions, $dimDenotation, $dimValue));
    }

    public function AddFreeDimensionOverrideByName($sessionOptions, $dimName, int $dimValue): void
    {
        $this->checkStatus((($this->api)->AddFreeDimensionOverrideByName)($sessionOptions, $dimName, $dimValue));
    }

    public function SetSessionGraphOptimizationLevel($sessionOptions, $optimizationLevel): void
    {
        $this->checkStatus((($this->api)->SetSessionGraphOptimizationLevel)($sessionOptions, $optimizationLevel));
    }

    public function SetInterOpNumThreads($sessionOptions, $optimizationLevel): void
    {
        $this->checkStatus((($this->api)->SetInterOpNumThreads)($sessionOptions, $optimizationLevel));
    }

    public function SetIntraOpNumThreads($sessionOptions, $optimizationLevel): void
    {
        $this->checkStatus((($this->api)->SetIntraOpNumThreads)($sessionOptions, $optimizationLevel));
    }

    public function SetSessionLogSeverityLevel($sessionOptions, $optimizationLevel): void
    {
        $this->checkStatus((($this->api)->SetSessionLogSeverityLevel)($sessionOptions, $optimizationLevel));
    }

    public function SetSessionLogVerbosityLevel($sessionOptions, $optimizationLevel): void
    {
        $this->checkStatus((($this->api)->SetSessionLogVerbosityLevel)($sessionOptions, $optimizationLevel));
    }

    public function SetSessionLogId($sessionOptions, $optimizationLevel): void
    {
        $this->checkStatus((($this->api)->SetSessionLogId)($sessionOptions, $optimizationLevel));
    }

    public function SetOptimizedModelFilePath($sessionOptions, $optimizationLevel): void
    {
        $this->checkStatus((($this->api)->SetOptimizedModelFilePath)($sessionOptions, $optimizationLevel));
    }

    public function AddSessionConfigEntry($sessionOptions, $configKey, $configValue): void
    {
        $this->checkStatus((($this->api)->AddSessionConfigEntry)($sessionOptions, $configKey, $configValue));
    }

    public function CreateCUDAProviderOptions(): CData
    {
        $cudaOptions = $this->new('OrtCUDAProviderOptionsV2*');

        $this->checkStatus((($this->api)->CreateCUDAProviderOptions)(FFI::addr($cudaOptions)));

        return $cudaOptions;
    }

    public function SessionOptionsAppendExecutionProvider_CUDA_V2($sessionOptions, $cudaOptions): void
    {
        $this->checkStatus((($this->api)->SessionOptionsAppendExecutionProvider_CUDA_V2)($sessionOptions, $cudaOptions));
    }

    public function ReleaseCUDAProviderOptions($cudaOptions): void
    {
        (($this->api)->ReleaseCUDAProviderOptions)($cudaOptions);
    }

    public function OrtSessionOptionsAppendExecutionProvider_CoreML($sessionOptions, $coreMlFlags): void
    {
        $this->checkStatus((($this->api)->OrtSessionOptionsAppendExecutionProvider_CoreML)($sessionOptions, $coreMlFlags));
    }

    public function ReleaseSessionOptions($sessionOptions): void
    {
        (($this->api)->ReleaseSessionOptions)($sessionOptions);
    }

    public function SessionGetInputCount($session): int
    {
        $numInputNodes = $this->new('size_t');

        $this->checkStatus((($this->api)->SessionGetInputCount)($session, FFI::addr($numInputNodes)));

        return $numInputNodes->cdata;
    }

    public function SessionGetInputName($session, int $index, $allocator): string
    {
        $namePtr = $this->new('char*');

        $this->checkStatus((($this->api)->SessionGetInputName)($session, $index, $allocator, FFI::addr($namePtr)));

        $name = FFI::string($namePtr);

        $this->AllocatorFree($allocator, $namePtr);

        return $name;
    }

    public function SessionGetInputTypeInfo($session, int $index): CData
    {
        $typeInfo = $this->new('OrtTypeInfo*');

        $this->checkStatus((($this->api)->SessionGetInputTypeInfo)($session, $index, FFI::addr($typeInfo)));

        return $typeInfo;
    }

    public function SessionGetOutputCount($session): int
    {
        $numOutputNodes = $this->new('size_t');

        $this->checkStatus((($this->api)->SessionGetOutputCount)($session, FFI::addr($numOutputNodes)));

        return $numOutputNodes->cdata;
    }

    public function SessionGetOutputName($session, int $index, $allocator): string
    {
        $namePtr = $this->new('char*');

        $this->checkStatus((($this->api)->SessionGetOutputName)($session, $index, $allocator, FFI::addr($namePtr)));

        $name = FFI::string($namePtr);

        $this->AllocatorFree($allocator, $namePtr);

        return $name;
    }

    public function SessionGetOutputTypeInfo($session, int $index): CData
    {
        $typeInfo = $this->new('OrtTypeInfo*');

        $this->checkStatus((($this->api)->SessionGetOutputTypeInfo)($session, $index, FFI::addr($typeInfo)));

        return $typeInfo;
    }

    public function GetAvailableProviders(): array
    {
        $outPtr = $this->new('char**');
        $lengthPtr = $this->new('int');

        $this->checkStatus((($this->api)->GetAvailableProviders)(FFI::addr($outPtr), FFI::addr($lengthPtr)));

        return [$outPtr, $lengthPtr->cdata];
    }

    public function ReleaseAvailableProviders($ptr, int $length): void
    {
        (($this->api)->ReleaseAvailableProviders)($ptr, $length);
    }

    public function SessionEndProfiling($session, $allocator): string
    {
        $resultPtr = $this->new('char*');

        $this->checkStatus((($this->api)->SessionEndProfiling)($session, $allocator, FFI::addr($resultPtr)));

        $result = FFI::string($resultPtr);

        $this->AllocatorFree($allocator, $resultPtr);

        return $result;
    }

    public function SessionGetModelMetadata($session): CData
    {
        $metadata = $this->new('OrtModelMetadata*');

        $this->checkStatus((($this->api)->SessionGetModelMetadata)($session, FFI::addr($metadata)));

        return $metadata;
    }

    public function ModelMetadataGetCustomMetadataMapKeys($metadata, $allocator): array
    {
        $keyPtrs = $this->new('char**');
        $numKeys = $this->new('int64_t');

        $this->checkStatus((($this->api)->ModelMetadataGetCustomMetadataMapKeys)($metadata, $allocator, FFI::addr($keyPtrs), FFI::addr($numKeys)));

        $keys = [];

        for ($i = 0; $i < $numKeys->cdata; $i++) {
            $keys[] = FFI::string($keyPtrs[$i]);
        }

        $this->AllocatorFree($allocator, $keyPtrs);

        return [$keys, $numKeys->cdata];
    }

    public function ModelMetadataLookupCustomMetadataMap($metadata, $allocator, $key): string
    {
        $valuePtr = $this->new('char*');

        $this->checkStatus((($this->api)->ModelMetadataLookupCustomMetadataMap)($metadata, $allocator, $key, FFI::addr($valuePtr)));

        $value = FFI::string($valuePtr);

        $this->AllocatorFree($allocator, $valuePtr);

        return $value;
    }

    public function ModelMetadataGetDescription($metadata, $allocator): string
    {
        $descriptionPtr = $this->new('char*');

        $this->checkStatus((($this->api)->ModelMetadataGetDescription)($metadata, $allocator, FFI::addr($descriptionPtr)));

        $description = FFI::string($descriptionPtr);

        $this->AllocatorFree($allocator, $descriptionPtr);

        return $description;
    }

    public function ModelMetadataGetDomain($metadata, $allocator): string
    {
        $domainPtr = $this->new('char*');

        $this->checkStatus((($this->api)->ModelMetadataGetDomain)($metadata, $allocator, FFI::addr($domainPtr)));

        $domain = FFI::string($domainPtr);

        $this->AllocatorFree($allocator, $domainPtr);

        return $domain;
    }

    public function ModelMetadataGetGraphName($metadata, $allocator): string
    {
        $graphNamePtr = $this->new('char*');

        $this->checkStatus((($this->api)->ModelMetadataGetGraphName)($metadata, $allocator, FFI::addr($graphNamePtr)));

        $graphName = FFI::string($graphNamePtr);

        $this->AllocatorFree($allocator, $graphNamePtr);

        return $graphName;
    }

    public function ModelMetadataGetGraphDescription($metadata, $allocator): string
    {
        $graphDescriptionPtr = $this->new('char*');

        $this->checkStatus((($this->api)->ModelMetadataGetGraphDescription)($metadata, $allocator, FFI::addr($graphDescriptionPtr)));

        $graphDescription = FFI::string($graphDescriptionPtr);

        $this->AllocatorFree($allocator, $graphDescriptionPtr);

        return $graphDescription;
    }

    public function ModelMetadataGetProducerName($metadata, $allocator): string
    {
        $producerNamePtr = $this->new('char*');

        $this->checkStatus((($this->api)->ModelMetadataGetProducerName)($metadata, $allocator, FFI::addr($producerNamePtr)));

        $producerName = FFI::string($producerNamePtr);

        $this->AllocatorFree($allocator, $producerNamePtr);

        return $producerName;
    }

    public function ModelMetadataGetVersion($metadata): int
    {
        $version = $this->new('int64_t');

        $this->checkStatus((($this->api)->ModelMetadataGetVersion)($metadata, FFI::addr($version)));

        return $version->cdata;
    }

    public function ReleaseModelMetadata($metadata): void
    {
        (($this->api)->ReleaseModelMetadata)($metadata);
    }

    public function CreateRunOptions(): CData
    {
        $runOptions = $this->new('OrtRunOptions*');

        $this->checkStatus((($this->api)->CreateRunOptions)(FFI::addr($runOptions)));

        return $runOptions;
    }

    public function RunOptionsSetRunLogSeverityLevel($runOptions, int $logSeverityLevel): void
    {
        $this->checkStatus((($this->api)->RunOptionsSetRunLogSeverityLevel)($runOptions, $logSeverityLevel));
    }

    public function RunOptionsSetRunLogVerbosityLevel($runOptions, int $logVerbosityLevel): void
    {
        $this->checkStatus((($this->api)->RunOptionsSetRunLogVerbosityLevel)($runOptions, $logVerbosityLevel));
    }

    public function RunOptionsSetRunTag($runOptions, string $logId): void
    {
        $this->checkStatus((($this->api)->RunOptionsSetRunTag)($runOptions, $logId));
    }

    public function RunOptionsSetTerminate($runOptions): void
    {
        $this->checkStatus((($this->api)->RunOptionsSetTerminate)($runOptions));
    }

    public function RunOptionsUnsetTerminate($runOptions): void
    {
        $this->checkStatus((($this->api)->RunOptionsUnsetTerminate)($runOptions));
    }

    public function Run($session, $runOptions, $inputNames, $inputs, int $inputLength, $outputNames, int $outputLength): CData
    {
        $outputTensor = $this->new("OrtValue*[$outputLength]");

        $this->checkStatus((($this->api)->Run)($session, $runOptions, $inputNames, $inputs, $inputLength, $outputNames, $outputLength, $outputTensor));

        return $outputTensor;
    }

    public function ReleaseRunOptions($runOptions): void
    {
        (($this->api)->ReleaseRunOptions)($runOptions);
    }

    public function GetTensorElementType($info): ?CData
    {
        $type = $this->new('ONNXTensorElementDataType');

        $this->checkStatus((($this->api)->GetTensorElementType)($info, FFI::addr($type)));

        return $type;
    }

    public function GetDimensionsCount($info): int
    {
        $numDims = $this->new('size_t');
        $this->checkStatus((($this->api)->GetDimensionsCount)($info, FFI::addr($numDims)));

        return $numDims->cdata;
    }

    public function GetDimensions($info, int $numDims): array
    {
        $nodeDims = $this->new("int64_t[$numDims]");

        $this->checkStatus((($this->api)->GetDimensions)($info, $nodeDims, $numDims));

        $dims = [];

        for ($i = 0; $i < $numDims; $i++) {
            $dims[] = $nodeDims[$i];
        }

        return $dims;
    }

    public function GetSymbolicDimensions($info, int $numDims): CData
    {
        $symbolicDims = $this->new("char*[$numDims]");

        $this->checkStatus((($this->api)->GetSymbolicDimensions)($info, $symbolicDims, $numDims));

        return $symbolicDims;
    }

    public function GetOnnxTypeFromTypeInfo($typeInfo): CData
    {
        $onnxType = $this->new('ONNXType');

        $this->checkStatus((($this->api)->GetOnnxTypeFromTypeInfo)($typeInfo, FFI::addr($onnxType)));

        return $onnxType;
    }

    public function CastTypeInfoToTensorInfo($typeInfo): CData
    {
        $tensorInfo = $this->new('OrtTensorTypeAndShapeInfo*');

        $this->checkStatus((($this->api)->CastTypeInfoToTensorInfo)($typeInfo, FFI::addr($tensorInfo)));

        return $tensorInfo;
    }

    public function CastTypeInfoToSequenceTypeInfo($typeInfo): CData
    {
        $sequenceTypeInfo = $this->new('OrtSequenceTypeInfo*');

        $this->checkStatus((($this->api)->CastTypeInfoToSequenceTypeInfo)($typeInfo, FFI::addr($sequenceTypeInfo)));

        return $sequenceTypeInfo;
    }

    public function CastTypeInfoToMapTypeInfo($typeInfo): CData
    {
        $mapTypeInfo = $this->new('OrtMapTypeInfo*');

        $this->checkStatus((($this->api)->CastTypeInfoToMapTypeInfo)($typeInfo, FFI::addr($mapTypeInfo)));

        return $mapTypeInfo;
    }

    public function GetSequenceElementType($sequenceTypeInfo): CData
    {
        $nestedTypeInfo = $this->new('OrtTypeInfo*');

        $this->checkStatus((($this->api)->GetSequenceElementType)($sequenceTypeInfo, FFI::addr($nestedTypeInfo)));

        return $nestedTypeInfo;
    }

    public function GetMapKeyType($mapTypeInfo): CData
    {
        $keyType = $this->new('ONNXTensorElementDataType');

        $this->checkStatus((($this->api)->GetMapKeyType)($mapTypeInfo, FFI::addr($keyType)));

        return $keyType;
    }

    public function GetMapValueType($mapTypeInfo): CData
    {
        $keyType = $this->new('OrtTypeInfo');

        $this->checkStatus((($this->api)->GetMapValueType)($mapTypeInfo, FFI::addr($keyType)));

        return $keyType;
    }

    public function GetStringTensorDataLength($value): int
    {
        $len = $this->new('size_t');

        $this->checkStatus((($this->api)->GetStringTensorDataLength)($value, FFI::addr($len)));

        return $len->cdata;
    }

    public function GetStringTensorContent($value, int $len, int $offsetsLength): array
    {
        $s = $this->new("char[$len]");
        $offsets = $this->new("size_t[$offsetsLength]");

        $this->checkStatus((($this->api)->GetStringTensorContent)($value, $s, $len, $offsets, $offsetsLength));

        return [$s, $offsets];
    }

    public function GetValueType($value): CData
    {
        $outType = $this->new('ONNXType');

        $this->checkStatus((($this->api)->GetValueType)($value, FFI::addr($outType)));

        return $outType;
    }

    public function GetValueCount($value): int
    {
        $out = $this->new('size_t');

        $this->checkStatus((($this->api)->GetValueCount)($value, FFI::addr($out)));

        return $out->cdata;
    }

    public function GetValue($value, int $index, $allocator): CData
    {
        $seq = $this->new('OrtValue*');

        $this->checkStatus((($this->api)->GetValue)($value, $index, $allocator, FFI::addr($seq)));

        return $seq;
    }

    public function ReleaseValue($value): void
    {
        (($this->api)->ReleaseValue)($value);
    }

    public function GetTensorTypeAndShape($value): CData
    {
        $typeInfo = $this->new('OrtTensorTypeAndShapeInfo*');

        $this->checkStatus((($this->api)->GetTensorTypeAndShape)($value, FFI::addr($typeInfo)));

        return $typeInfo;
    }

    public function ReleaseTensorTypeAndShapeInfo($info): void
    {
        (($this->api)->ReleaseTensorTypeAndShapeInfo)($info);
    }

    public function GetTensorMutableData($value): CData
    {
        $tensorData = $this->new('void*');

        $this->checkStatus((($this->api)->GetTensorMutableData)($value, FFI::addr($tensorData)));

        return $tensorData;
    }

    public function GetTensorShapeElementCount($info): int
    {
        $outSize = $this->new('size_t');

        $this->checkStatus((($this->api)->GetTensorShapeElementCount)($info, FFI::addr($outSize)));

        return $outSize->cdata;
    }

    public function CreateCpuMemoryInfo(int $type, int $memType): CData
    {
        $allocatorInfo = $this->new('OrtMemoryInfo*');
        $this->checkStatus((($this->api)->CreateCpuMemoryInfo)($type, $memType, FFI::addr($allocatorInfo)));

        return $allocatorInfo;
    }

    public function ReleaseMemoryInfo($info): void
    {
        (($this->api)->ReleaseMemoryInfo)($info);
    }

    public function CreateTensorAsOrtValue($allocator, $shape, int $shapeLen, $type, $out): void
    {
        $this->checkStatus((($this->api)->CreateTensorAsOrtValue)($allocator, $shape, $shapeLen, $type, $out));
    }

    public function FillStringTensor($value, $s, $len): void
    {
        $this->checkStatus((($this->api)->FillStringTensor)($value, $s, $len));
    }

    public function CreateTensorWithDataAsOrtValue($info, $pData, $pDataLen, $shape, int $shapeLen, $type, $out): void
    {
        $this->checkStatus((($this->api)->CreateTensorWithDataAsOrtValue)($info, $pData, $pDataLen, $shape, $shapeLen, $type, $out));
    }

    public function GetAllocatorWithDefaultOptions(): CData
    {
        $allocator = $this->new('OrtAllocator*');
        $this->checkStatus((($this->api)->GetAllocatorWithDefaultOptions)(FFI::addr($allocator)));

        return $allocator;
    }

    public function ReleaseAllocator($allocator): void
    {
        (($this->api)->ReleaseAllocator)($allocator);
    }

    public function AllocatorFree($allocator, $ptr): void
    {
        (($this->api)->AllocatorFree)($allocator, $ptr);
    }

    public function CreateEnv(int $logSecurityLevel, string $logId): ?CData
    {
        $env = $this->new('OrtEnv*');

        (($this->api)->CreateEnv)($logSecurityLevel, $logId, FFI::addr($env));

        // disable telemetry
        // https://github.com/microsoft/onnxruntime/blob/master/docs/Privacy.md
        $this->checkStatus((($this->api)->DisableTelemetryEvents)($env));

        return $env;
    }

    public function ReleaseEnv($env): void
    {
        (($this->api)->ReleaseEnv)($env);
    }
}
