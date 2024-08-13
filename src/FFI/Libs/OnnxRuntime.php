<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI\Libs;

use Codewithkyrian\Transformers\FFI\Lib;
use Exception;
use FFI;
use FFI\CData;
use FFI\CType;
use RuntimeException;

class OnnxRuntime
{
    protected static FFI $ffi;
    protected static mixed $api;


    /**
     * Returns an instance of the FFI class after checking if it has already been instantiated.
     * If not, it creates a new instance by defining the header contents and library path.
     *
     * @return FFI The FFI instance.
     * @throws Exception
     */
    protected static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            self::$ffi = FFI::cdef(
                file_get_contents(Lib::OnnxRuntime->header()),
                Lib::OnnxRuntime->library()
            );
        }

        return self::$ffi;
    }

    /**
     * Creates a new instance of the specified type.
     *
     * @param string $type The type of the instance to create.
     * @param bool $owned Whether the instance should be owned. Default is true.
     * @param bool $persistent Whether the instance should be persistent. Default is false.
     *
     * @return CData|null The created instance, or null if the creation failed.
     * @throws Exception
     */
    public static function new(string $type, bool $owned = true, bool $persistent = false): ?CData
    {
        return self::ffi()->new($type, $owned, $persistent);
    }

    /**
     * Casts a pointer to a different type.
     *
     * @param CType|string $type The type to cast to.
     * @param CData|int|float|bool|null $ptr The pointer to cast.
     *
     * @return ?CData The cast pointer, or null if the cast failed.
     * @throws Exception
     */
    public static function cast(CType|string$type, CData|int|float|bool|null$ptr): ?CData
    {
        return self::ffi()->cast($type, $ptr);
    }

    /**
     * Retrieves the value of the enum constant with the given name.
     *
     * @param string $name The name of the enum constant.
     *
     * @return mixed The value of the enum constant.
     * @throws Exception
     */
    public static function enum(string $name): mixed
    {
        return self::ffi()->{$name};
    }

    /**
     * Returns the version of the library as a string.
     *
     * @return string The version of the library.
     */
    public static function version(): string
    {
        return (self::ffi()->OrtGetApiBase()[0]->GetVersionString)();
    }

    public static function api(): mixed
    {
        if (!isset(self::$api)) {
            self::$api = (self::ffi()->OrtGetApiBase()[0]->GetApi)(11)[0];
        }

        return self::$api;
    }

    private static function checkStatus($status): void
    {
        if (!is_null($status)) {
            $message = (self::api()->GetErrorMessage)($status);
            (self::api()->ReleaseStatus)($status);
            throw new RuntimeException($message);
        }
    }

    public static function CreateSession($env, $modelPath, $options): CData
    {
        $session = self::new('OrtSession*');

        self::checkStatus((self::api()->CreateSession)($env, $modelPath, $options, FFI::addr($session)));

        return $session;
    }

    public static function CreateSessionFromArray($env, $modelData, $modelDataLength, $options): CData
    {
        $session = self::new('OrtSession*');

        self::checkStatus((self::api()->CreateSessionFromArray)($env, $modelData, $modelDataLength, $options, FFI::addr($session)));

        return $session;
    }

    public static function ReleaseSession($session): void
    {
        (self::api()->ReleaseSession)($session);
    }

    public static function CreateSessionOptions(): CData
    {
        $sessionOptions = self::new('OrtSessionOptions*');

        self::checkStatus((self::api()->CreateSessionOptions)(FFI::addr($sessionOptions)));

        return $sessionOptions;
    }

    public static function EnableCpuMemArena($sessionOptions): void
    {
        self::checkStatus((self::api()->EnableCpuMemArena)($sessionOptions));
    }

    public static function DisableCpuMemArena($sessionOptions): void
    {
        self::checkStatus((self::api()->DisableCpuMemArena)($sessionOptions));
    }

    public static function EnableMemPattern($sessionOptions): void
    {
        self::checkStatus((self::api()->EnableMemPattern)($sessionOptions));
    }

    public static function DisableMemPattern($sessionOptions): void
    {
        self::checkStatus((self::api()->DisableMemPattern)($sessionOptions));
    }

    public static function EnableProfiling($sessionOptions, $profileFilePrefix): void
    {
        self::checkStatus((self::api()->EnableProfiling)($sessionOptions, $profileFilePrefix));
    }

    public static function DisableProfiling($sessionOptions): void
    {
        self::checkStatus((self::api()->DisableProfiling)($sessionOptions));
    }

    public static function SetSessionExecutionMode($sessionOptions, $executionMode): void
    {
        self::checkStatus((self::api()->SetSessionExecutionMode)($sessionOptions, $executionMode));
    }

    public static function AddFreeDimensionOverride($sessionOptions, $dimDenotation, int $dimValue): void
    {
        self::checkStatus((self::api()->AddFreeDimensionOverride)($sessionOptions, $dimDenotation, $dimValue));
    }

    public static function AddFreeDimensionOverrideByName($sessionOptions, $dimName, int $dimValue): void
    {
        self::checkStatus((self::api()->AddFreeDimensionOverrideByName)($sessionOptions, $dimName, $dimValue));
    }

    public static function SetSessionGraphOptimizationLevel($sessionOptions, $optimizationLevel): void
    {
        self::checkStatus((self::api()->SetSessionGraphOptimizationLevel)($sessionOptions, $optimizationLevel));
    }

    public static function SetInterOpNumThreads($sessionOptions, $optimizationLevel): void
    {
        self::checkStatus((self::api()->SetInterOpNumThreads)($sessionOptions, $optimizationLevel));
    }

    public static function SetIntraOpNumThreads($sessionOptions, $optimizationLevel): void
    {
        self::checkStatus((self::api()->SetIntraOpNumThreads)($sessionOptions, $optimizationLevel));
    }

    public static function SetSessionLogSeverityLevel($sessionOptions, $optimizationLevel): void
    {
        self::checkStatus((self::api()->SetSessionLogSeverityLevel)($sessionOptions, $optimizationLevel));
    }

    public static function SetSessionLogVerbosityLevel($sessionOptions, $optimizationLevel): void
    {
        self::checkStatus((self::api()->SetSessionLogVerbosityLevel)($sessionOptions, $optimizationLevel));
    }

    public static function SetSessionLogId($sessionOptions, $optimizationLevel): void
    {
        self::checkStatus((self::api()->SetSessionLogId)($sessionOptions, $optimizationLevel));
    }

    public static function SetOptimizedModelFilePath($sessionOptions, $optimizationLevel): void
    {
        self::checkStatus((self::api()->SetOptimizedModelFilePath)($sessionOptions, $optimizationLevel));
    }

    public static function AddSessionConfigEntry($sessionOptions, $configKey, $configValue): void
    {
        self::checkStatus((self::api()->AddSessionConfigEntry)($sessionOptions, $configKey, $configValue));
    }

    public static function CreateCUDAProviderOptions(): CData
    {
        $cudaOptions = self::new('OrtCUDAProviderOptionsV2*');

        self::checkStatus((self::api()->CreateCUDAProviderOptions)(FFI::addr($cudaOptions)));

        return $cudaOptions;
    }

    public static function SessionOptionsAppendExecutionProvider_CUDA_V2($sessionOptions, $cudaOptions): void
    {
        self::checkStatus((self::api()->SessionOptionsAppendExecutionProvider_CUDA_V2)($sessionOptions, $cudaOptions));
    }

    public static function ReleaseCUDAProviderOptions($cudaOptions): void
    {
        (self::api()->ReleaseCUDAProviderOptions)($cudaOptions);
    }

    public static function OrtSessionOptionsAppendExecutionProvider_CoreML($sessionOptions, $coreMlFlags): void
    {
        self::checkStatus((self::api()->OrtSessionOptionsAppendExecutionProvider_CoreML)($sessionOptions, $coreMlFlags));
    }

    public static function ReleaseSessionOptions($sessionOptions): void
    {
        (self::api()->ReleaseSessionOptions)($sessionOptions);
    }

    public static function SessionGetInputCount($session): int
    {
        $numInputNodes = self::new('size_t');

        self::checkStatus((self::api()->SessionGetInputCount)($session, FFI::addr($numInputNodes)));

        return $numInputNodes->cdata;
    }

    public static function SessionGetInputName($session, int $index, $allocator): string
    {
        $namePtr = self::new('char*');

        self::checkStatus((self::api()->SessionGetInputName)($session, $index, $allocator, FFI::addr($namePtr)));

        $name = FFI::string($namePtr);

        self::AllocatorFree($allocator, $namePtr);

        return $name;
    }

    public static function SessionGetInputTypeInfo($session, int $index): CData
    {
        $typeInfo = self::new('OrtTypeInfo*');

        self::checkStatus((self::api()->SessionGetInputTypeInfo)($session, $index, FFI::addr($typeInfo)));

        return $typeInfo;
    }

    public static function SessionGetOutputCount($session): int
    {
        $numOutputNodes = self::new('size_t');

        self::checkStatus((self::api()->SessionGetOutputCount)($session, FFI::addr($numOutputNodes)));

        return $numOutputNodes->cdata;
    }

    public static function SessionGetOutputName($session, int $index, $allocator): string
    {
        $namePtr = self::new('char*');

        self::checkStatus((self::api()->SessionGetOutputName)($session, $index, $allocator, FFI::addr($namePtr)));

        $name = FFI::string($namePtr);

        self::AllocatorFree($allocator, $namePtr);

        return $name;
    }

    public static function SessionGetOutputTypeInfo($session, int $index): CData
    {
        $typeInfo = self::new('OrtTypeInfo*');

        self::checkStatus((self::api()->SessionGetOutputTypeInfo)($session, $index, FFI::addr($typeInfo)));

        return $typeInfo;
    }

    public static function GetAvailableProviders(): array
    {
        $outPtr = self::new('char**');
        $lengthPtr = self::new('int');

        self::checkStatus((self::api()->GetAvailableProviders)(FFI::addr($outPtr), FFI::addr($lengthPtr)));

        return [$outPtr, $lengthPtr->cdata];
    }

    public static function ReleaseAvailableProviders($ptr, int $length): void
    {
        (self::api()->ReleaseAvailableProviders)($ptr, $length);
    }

    public static function SessionEndProfiling($session, $allocator): string
    {
        $resultPtr = self::new('char*');

        self::checkStatus((self::api()->SessionEndProfiling)($session, $allocator, FFI::addr($resultPtr)));

        $result = FFI::string($resultPtr);

        self::AllocatorFree($allocator, $resultPtr);

        return $result;
    }

    public static function SessionGetModelMetadata($session): CData
    {
        $metadata = self::new('OrtModelMetadata*');

        self::checkStatus((self::api()->SessionGetModelMetadata)($session, FFI::addr($metadata)));

        return $metadata;
    }

    public static function ModelMetadataGetCustomMetadataMapKeys($metadata, $allocator): array
    {
        $keyPtrs = self::new('char**');
        $numKeys = self::new('int64_t');

        self::checkStatus((self::api()->ModelMetadataGetCustomMetadataMapKeys)($metadata, $allocator, FFI::addr($keyPtrs), FFI::addr($numKeys)));

        $keys = [];

        for ($i = 0; $i < $numKeys->cdata; $i++) {
            $keys[] = FFI::string($keyPtrs[$i]);
        }

        self::AllocatorFree($allocator, $keyPtrs);

        return [$keys, $numKeys->cdata];
    }

    public static function ModelMetadataLookupCustomMetadataMap($metadata, $allocator, $key): string
    {
        $valuePtr = self::new('char*');

        self::checkStatus((self::api()->ModelMetadataLookupCustomMetadataMap)($metadata, $allocator, $key, FFI::addr($valuePtr)));

        $value = FFI::string($valuePtr);

        self::AllocatorFree($allocator, $valuePtr);

        return $value;
    }

    public static function ModelMetadataGetDescription($metadata, $allocator): string
    {
        $descriptionPtr = self::new('char*');

        self::checkStatus((self::api()->ModelMetadataGetDescription)($metadata, $allocator, FFI::addr($descriptionPtr)));

        $description = FFI::string($descriptionPtr);

        self::AllocatorFree($allocator, $descriptionPtr);

        return $description;
    }

    public static function ModelMetadataGetDomain($metadata, $allocator): string
    {
        $domainPtr = self::new('char*');

        self::checkStatus((self::api()->ModelMetadataGetDomain)($metadata, $allocator, FFI::addr($domainPtr)));

        $domain = FFI::string($domainPtr);

        self::AllocatorFree($allocator, $domainPtr);

        return $domain;
    }

    public static function ModelMetadataGetGraphName($metadata, $allocator): string
    {
        $graphNamePtr = self::new('char*');

        self::checkStatus((self::api()->ModelMetadataGetGraphName)($metadata, $allocator, FFI::addr($graphNamePtr)));

        $graphName = FFI::string($graphNamePtr);

        self::AllocatorFree($allocator, $graphNamePtr);

        return $graphName;
    }

    public static function ModelMetadataGetGraphDescription($metadata, $allocator): string
    {
        $graphDescriptionPtr = self::new('char*');

        self::checkStatus((self::api()->ModelMetadataGetGraphDescription)($metadata, $allocator, FFI::addr($graphDescriptionPtr)));

        $graphDescription = FFI::string($graphDescriptionPtr);

        self::AllocatorFree($allocator, $graphDescriptionPtr);

        return $graphDescription;
    }

    public static function ModelMetadataGetProducerName($metadata, $allocator): string
    {
        $producerNamePtr = self::new('char*');

        self::checkStatus((self::api()->ModelMetadataGetProducerName)($metadata, $allocator, FFI::addr($producerNamePtr)));

        $producerName = FFI::string($producerNamePtr);

        self::AllocatorFree($allocator, $producerNamePtr);

        return $producerName;
    }

    public static function ModelMetadataGetVersion($metadata): int
    {
        $version = self::new('int64_t');

        self::checkStatus((self::api()->ModelMetadataGetVersion)($metadata, FFI::addr($version)));

        return $version->cdata;
    }

    public static function ReleaseModelMetadata($metadata): void
    {
        (self::api()->ReleaseModelMetadata)($metadata);
    }

    public static function CreateRunOptions(): CData
    {
        $runOptions = self::new('OrtRunOptions*');

        self::checkStatus((self::api()->CreateRunOptions)(FFI::addr($runOptions)));

        return $runOptions;
    }

    public static function RunOptionsSetRunLogSeverityLevel($runOptions, int $logSeverityLevel): void
    {
        self::checkStatus((self::api()->RunOptionsSetRunLogSeverityLevel)($runOptions, $logSeverityLevel));
    }

    public static function RunOptionsSetRunLogVerbosityLevel($runOptions, int $logVerbosityLevel): void
    {
        self::checkStatus((self::api()->RunOptionsSetRunLogVerbosityLevel)($runOptions, $logVerbosityLevel));
    }

    public static function RunOptionsSetRunTag($runOptions, string $logId): void
    {
        self::checkStatus((self::api()->RunOptionsSetRunTag)($runOptions, $logId));
    }

    public static function RunOptionsSetTerminate($runOptions): void
    {
        self::checkStatus((self::api()->RunOptionsSetTerminate)($runOptions));
    }

    public static function RunOptionsUnsetTerminate($runOptions): void
    {
        self::checkStatus((self::api()->RunOptionsUnsetTerminate)($runOptions));
    }

    public static function Run($session, $runOptions, $inputNames, $inputs, int $inputLength, $outputNames, int $outputLength): CData
    {
        $outputTensor = self::new("OrtValue*[$outputLength]");

        self::checkStatus((self::api()->Run)($session, $runOptions, $inputNames, $inputs, $inputLength, $outputNames, $outputLength, $outputTensor));

        return $outputTensor;
    }

    public static function ReleaseRunOptions($runOptions): void
    {
        (self::api()->ReleaseRunOptions)($runOptions);
    }

    public static function GetTensorElementType($info): ?CData
    {
        $type = self::new('ONNXTensorElementDataType');

        self::checkStatus((self::api()->GetTensorElementType)($info, FFI::addr($type)));

        return $type;
    }

    public static function GetDimensionsCount($info): int
    {
        $numDims = self::new('size_t');
        self::checkStatus((self::api()->GetDimensionsCount)($info, FFI::addr($numDims)));

        return $numDims->cdata;
    }

    public static function GetDimensions($info, int $numDims): array
    {
        $nodeDims = self::new("int64_t[$numDims]");

        self::checkStatus((self::api()->GetDimensions)($info, $nodeDims, $numDims));

        $dims = [];

        $n = count($nodeDims);
        for ($i = 0; $i < $n; $i++) {
            $dims[] = $nodeDims[$i];
        }

        return $dims;
    }

    public static function GetSymbolicDimensions($info, int $numDims): CData
    {
        $symbolicDims = self::new("char*[$numDims]");

        self::checkStatus((self::api()->GetSymbolicDimensions)($info, $symbolicDims, $numDims));

        return $symbolicDims;
    }

    public static function GetOnnxTypeFromTypeInfo($typeInfo): CData
    {
        $onnxType = self::new('ONNXType');

        self::checkStatus((self::api()->GetOnnxTypeFromTypeInfo)($typeInfo, FFI::addr($onnxType)));

        return $onnxType;
    }

    public static function CastTypeInfoToTensorInfo($typeInfo): CData
    {
        $tensorInfo = self::new('OrtTensorTypeAndShapeInfo*');

        self::checkStatus((self::api()->CastTypeInfoToTensorInfo)($typeInfo, FFI::addr($tensorInfo)));

        return $tensorInfo;
    }

    public static function CastTypeInfoToSequenceTypeInfo($typeInfo): CData
    {
        $sequenceTypeInfo = self::new('OrtSequenceTypeInfo*');

        self::checkStatus((self::api()->CastTypeInfoToSequenceTypeInfo)($typeInfo, FFI::addr($sequenceTypeInfo)));

        return $sequenceTypeInfo;
    }

    public static function CastTypeInfoToMapTypeInfo($typeInfo): CData
    {
        $mapTypeInfo = self::new('OrtMapTypeInfo*');

        self::checkStatus((self::api()->CastTypeInfoToMapTypeInfo)($typeInfo, FFI::addr($mapTypeInfo)));

        return $mapTypeInfo;
    }

    public static function GetSequenceElementType($sequenceTypeInfo): CData
    {
        $nestedTypeInfo = self::new('OrtTypeInfo*');

        self::checkStatus((self::api()->GetSequenceElementType)($sequenceTypeInfo, FFI::addr($nestedTypeInfo)));

        return $nestedTypeInfo;
    }

    public static function GetMapKeyType($mapTypeInfo): CData
    {
        $keyType = self::new('ONNXTensorElementDataType');

        self::checkStatus((self::api()->GetMapKeyType)($mapTypeInfo, FFI::addr($keyType)));

        return $keyType;
    }

    public static function GetMapValueType($mapTypeInfo): CData
    {
        $keyType = self::new('OrtTypeInfo');

        self::checkStatus((self::api()->GetMapValueType)($mapTypeInfo, FFI::addr($keyType)));

        return $keyType;
    }

    public static function GetStringTensorDataLength($value): int
    {
        $len = self::new('size_t');

        self::checkStatus((self::api()->GetStringTensorDataLength)($value, FFI::addr($len)));

        return $len->cdata;
    }

    public static function GetStringTensorContent($value, int $len, int $offsetsLength): array
    {
        $s = self::new("char[$len]");
        $offsets = self::new("size_t[$offsetsLength]");

        self::checkStatus((self::api()->GetStringTensorContent)($value, $s, $len, $offsets, $offsetsLength));

        return [$s, $offsets];
    }

    public static function GetValueType($value): CData
    {
        $outType = self::new('ONNXType');

        self::checkStatus((self::api()->GetValueType)($value, FFI::addr($outType)));

        return $outType;
    }

    public static function GetValueCount($value): int
    {
        $out = self::new('size_t');

        self::checkStatus((self::api()->GetValueCount)($value, FFI::addr($out)));

        return $out->cdata;
    }

    public static function GetValue($value, int $index, $allocator): CData
    {
        $seq = self::new('OrtValue*');

        self::checkStatus((self::api()->GetValue)($value, $index, $allocator, FFI::addr($seq)));

        return $seq;
    }

    public static function ReleaseValue($value): void
    {
        (self::api()->ReleaseValue)($value);
    }

    public static function GetTensorTypeAndShape($value): CData
    {
        $typeInfo = self::new('OrtTensorTypeAndShapeInfo*');

        self::checkStatus((self::api()->GetTensorTypeAndShape)($value, FFI::addr($typeInfo)));

        return $typeInfo;
    }

    public static function ReleaseTensorTypeAndShapeInfo($info): void
    {
        (self::api()->ReleaseTensorTypeAndShapeInfo)($info);
    }

    public static function GetTensorMutableData($value): CData
    {
        $tensorData = self::new('void*');

        self::checkStatus((self::api()->GetTensorMutableData)($value, FFI::addr($tensorData)));

        return $tensorData;
    }

    public static function GetTensorShapeElementCount($info): int
    {
        $outSize = self::new('size_t');

        self::checkStatus((self::api()->GetTensorShapeElementCount)($info, FFI::addr($outSize)));

        return $outSize->cdata;
    }

    public static function CreateCpuMemoryInfo(int $type, int $memType): CData
    {
        $allocatorInfo = self::new('OrtMemoryInfo*');
        self::checkStatus((self::api()->CreateCpuMemoryInfo)($type, $memType, FFI::addr($allocatorInfo)));

        return $allocatorInfo;
    }

    public static function ReleaseMemoryInfo($info): void
    {
        (self::api()->ReleaseMemoryInfo)($info);
    }

    public static function CreateTensorAsOrtValue($allocator, $shape, int $shapeLen, $type, $out): void
    {
        self::checkStatus((self::api()->CreateTensorAsOrtValue)($allocator, $shape, $shapeLen, $type, $out));
    }

    public static function FillStringTensor($value, $s, $len): void
    {
        self::checkStatus((self::api()->FillStringTensor)($value, $s, $len));
    }

    public static function CreateTensorWithDataAsOrtValue($info, $pData, $pDataLen, $shape, int $shapeLen, $type, $out): void
    {
        self::checkStatus((self::api()->CreateTensorWithDataAsOrtValue)($info, $pData, $pDataLen, $shape, $shapeLen, $type, $out));
    }

    public static function GetAllocatorWithDefaultOptions(): CData
    {
        $allocator = self::new('OrtAllocator*');
        self::checkStatus((self::api()->GetAllocatorWithDefaultOptions)(FFI::addr($allocator)));

        return $allocator;
    }

    public static function ReleaseAllocator($allocator): void
    {
        (self::api()->ReleaseAllocator)($allocator);
    }

    public static function AllocatorFree($allocator, $ptr): void
    {
        (self::api()->AllocatorFree)($allocator, $ptr);
    }

    public static function CreateEnv(int $logSecurityLevel, string $logId): ?CData
    {
        $env = static::new('OrtEnv*');

        (self::api()->CreateEnv)($logSecurityLevel, $logId, FFI::addr($env));

        // disable telemetry
        // https://github.com/microsoft/onnxruntime/blob/master/docs/Privacy.md
        self::checkStatus((OnnxRuntime::api()->DisableTelemetryEvents)($env));

        return $env;
    }
}