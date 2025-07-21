#ifndef ORT_API_H
#define ORT_API_H

// https://github.com/microsoft/onnxruntime/blob/main/include/onnxruntime/core/session/onnxruntime_c_api.h
// keep same order

typedef enum ONNXTensorElementDataType {
    ONNX_TENSOR_ELEMENT_DATA_TYPE_UNDEFINED,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT8,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_INT8,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT16,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_INT16,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_INT32,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_INT64,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_STRING,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_BOOL,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_FLOAT16,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_DOUBLE,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT32,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_UINT64,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX64,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_COMPLEX128,
    ONNX_TENSOR_ELEMENT_DATA_TYPE_BFLOAT16
} ONNXTensorElementDataType;

typedef enum ONNXType {
    ONNX_TYPE_UNKNOWN,
    ONNX_TYPE_TENSOR,
    ONNX_TYPE_SEQUENCE,
    ONNX_TYPE_MAP,
    ONNX_TYPE_OPAQUE,
    ONNX_TYPE_SPARSETENSOR,
    ONNX_TYPE_OPTIONAL
} ONNXType;

typedef enum OrtLoggingLevel {
    ORT_LOGGING_LEVEL_VERBOSE,
    ORT_LOGGING_LEVEL_INFO,
    ORT_LOGGING_LEVEL_WARNING,
    ORT_LOGGING_LEVEL_ERROR,
    ORT_LOGGING_LEVEL_FATAL,
} OrtLoggingLevel;

typedef enum OrtErrorCode {
    ORT_OK,
    ORT_FAIL,
    ORT_INVALID_ARGUMENT,
    ORT_NO_SUCHFILE,
    ORT_NO_MODEL,
    ORT_ENGINE_ERROR,
    ORT_RUNTIME_EXCEPTION,
    ORT_INVALID_PROTOBUF,
    ORT_MODEL_LOADED,
    ORT_NOT_IMPLEMENTED,
    ORT_INVALID_GRAPH,
    ORT_EP_FAIL,
} OrtErrorCode;

struct OrtEnv;
typedef struct OrtEnv OrtEnv;

struct OrtStatus;
typedef struct OrtStatus OrtStatus;

struct OrtMemoryInfo;
typedef struct OrtMemoryInfo OrtMemoryInfo;

struct OrtIoBinding;
typedef struct OrtIoBinding OrtIoBinding;

struct OrtSession;
typedef struct OrtSession OrtSession;

struct OrtValue;
typedef struct OrtValue OrtValue;

struct OrtRunOptions;
typedef struct OrtRunOptions OrtRunOptions;

struct OrtTypeInfo;
typedef struct OrtTypeInfo OrtTypeInfo;

struct OrtTensorTypeAndShapeInfo;
typedef struct OrtTensorTypeAndShapeInfo OrtTensorTypeAndShapeInfo;

struct OrtSessionOptions;
typedef struct OrtSessionOptions OrtSessionOptions;

struct OrtCustomOpDomain;
typedef struct OrtCustomOpDomain OrtCustomOpDomain;

struct OrtMapTypeInfo;
typedef struct OrtMapTypeInfo OrtMapTypeInfo;

struct OrtSequenceTypeInfo;
typedef struct OrtSequenceTypeInfo OrtSequenceTypeInfo;

struct OrtModelMetadata;
typedef struct OrtModelMetadata OrtModelMetadata;

struct OrtThreadPoolParams;
typedef struct OrtThreadPoolParams OrtThreadPoolParams;

struct OrtThreadingOptions;
typedef struct OrtThreadingOptions OrtThreadingOptions;

struct OrtArenaCfg;
typedef struct OrtArenaCfg OrtArenaCfg;

struct OrtPrepackedWeightsContainer;
typedef struct OrtPrepackedWeightsContainer OrtPrepackedWeightsContainer;

struct OrtTensorRTProviderOptionsV2;
typedef struct OrtTensorRTProviderOptionsV2 OrtTensorRTProviderOptionsV2;



typedef enum COREMLFlags {
    COREML_FLAG_USE_NONE = 0x000,
    COREML_FLAG_USE_CPU_ONLY = 0x001,
    COREML_FLAG_ENABLE_ON_SUBGRAPH = 0x002,
    COREML_FLAG_ONLY_ENABLE_DEVICE_WITH_ANE = 0x004,
    COREML_FLAG_ONLY_ALLOW_STATIC_INPUT_SHAPES = 0x008,
    COREML_FLAG_CREATE_MLPROGRAM = 0x010,
    COREML_FLAG_LAST = COREML_FLAG_CREATE_MLPROGRAM
} COREMLFlags;

struct OrtOp;
typedef struct OrtOp OrtOp;

struct OrtOpAttr;
typedef struct Ort OrtOpAttr;

typedef struct OrtAllocator {
    uint32_t version;
    void*(* Alloc)(struct OrtAllocator* this_, size_t size);
    void(* Free)(struct OrtAllocator* this_, void* p);
    const struct OrtMemoryInfo*(* Info)(const struct OrtAllocator* this_);
} OrtAllocator;

typedef enum GraphOptimizationLevel {
    ORT_DISABLE_ALL = 0,
    ORT_ENABLE_BASIC = 1,
    ORT_ENABLE_EXTENDED = 2,
    ORT_ENABLE_ALL = 99
} GraphOptimizationLevel;

typedef enum ExecutionMode {
    ORT_SEQUENTIAL = 0,
    ORT_PARALLEL = 1,
} ExecutionMode;

struct OrtApi;
typedef struct OrtApi OrtApi;

struct OrtApiBase {
    const OrtApi*(* GetApi)(uint32_t version);
    const char*(* GetVersionString)(void);
};
typedef struct OrtApiBase OrtApiBase;

const OrtApiBase* OrtGetApiBase(void);

struct OrtApi {
    OrtStatus*(* CreateStatus)(OrtErrorCode code, const char* msg);
    OrtErrorCode(* GetErrorCode)(const OrtStatus* status);
    const char*(* GetErrorMessage)(const OrtStatus* status);
    OrtStatus*(* CreateEnv)(OrtLoggingLevel log_severity_level, const char* logid, OrtEnv** out);
    OrtStatus*(* CreateEnvWithCustomLogger)();
    OrtStatus*(* EnableTelemetryEvents)(const OrtEnv* env);
    OrtStatus*(* DisableTelemetryEvents)(const OrtEnv* env);
    OrtStatus*(* CreateSession)(const OrtEnv* env, const char* model_path, const OrtSessionOptions* options, OrtSession** out);
    OrtStatus*(* CreateSessionFromArray)(const OrtEnv* env, const void* model_data, size_t model_data_length, const OrtSessionOptions* options, OrtSession** out);
    OrtStatus*(* Run)(OrtSession* session, const OrtRunOptions* run_options, const char* const* input_names, const OrtValue* const* inputs, size_t input_len, const char* const* output_names, size_t output_names_len, OrtValue** outputs);
    OrtStatus*(* CreateSessionOptions)(OrtSessionOptions** options);
    OrtStatus*(* SetOptimizedModelFilePath)(OrtSessionOptions* options, const char* optimized_model_filepath);
    OrtStatus*(* CloneSessionOptions)();
    OrtStatus*(* SetSessionExecutionMode)(OrtSessionOptions* options, ExecutionMode execution_mode);
    OrtStatus*(* EnableProfiling)(OrtSessionOptions* options, const char* profile_file_prefix);
    OrtStatus*(* DisableProfiling)(OrtSessionOptions* options);
    OrtStatus*(* EnableMemPattern)(OrtSessionOptions* options);
    OrtStatus*(* DisableMemPattern)(OrtSessionOptions* options);
    OrtStatus*(* EnableCpuMemArena)(OrtSessionOptions* options);
    OrtStatus*(* DisableCpuMemArena)(OrtSessionOptions* options);
    OrtStatus*(* SetSessionLogId)(OrtSessionOptions* options, const char* logid);
    OrtStatus*(* SetSessionLogVerbosityLevel)(OrtSessionOptions* options, int session_log_verbosity_level);
    OrtStatus*(* SetSessionLogSeverityLevel)(OrtSessionOptions* options, int session_log_severity_level);
    OrtStatus*(* SetSessionGraphOptimizationLevel)(OrtSessionOptions* options, GraphOptimizationLevel graph_optimization_level);
    OrtStatus*(* SetIntraOpNumThreads)(OrtSessionOptions* options, int intra_op_num_threads);
    OrtStatus*(* SetInterOpNumThreads)(OrtSessionOptions* options, int inter_op_num_threads);
    OrtStatus*(* CreateCustomOpDomain)();
    OrtStatus*(* CustomOpDomain_Add)();
    OrtStatus*(* AddCustomOpDomain)();
    OrtStatus*(* RegisterCustomOpsLibrary)();
    OrtStatus*(* SessionGetInputCount)(const OrtSession* session, size_t* out);
    OrtStatus*(* SessionGetOutputCount)(const OrtSession* session, size_t* out);
    OrtStatus*(* SessionGetOverridableInitializerCount)();
    OrtStatus*(* SessionGetInputTypeInfo)(const OrtSession* session, size_t index, OrtTypeInfo** type_info);
    OrtStatus*(* SessionGetOutputTypeInfo)(const OrtSession* session, size_t index, OrtTypeInfo** type_info);
    OrtStatus*(* SessionGetOverridableInitializerTypeInfo)();
    OrtStatus*(* SessionGetInputName)(const OrtSession* session, size_t index, OrtAllocator* allocator, char** value);
    OrtStatus*(* SessionGetOutputName)(const OrtSession* session, size_t index, OrtAllocator* allocator, char** value);
    OrtStatus*(* SessionGetOverridableInitializerName)();
    OrtStatus*(* CreateRunOptions)(OrtRunOptions** out);
    OrtStatus*(* RunOptionsSetRunLogVerbosityLevel)(OrtRunOptions* options, int log_verbosity_level);
    OrtStatus*(* RunOptionsSetRunLogSeverityLevel)(OrtRunOptions* options, int log_severity_level);
    OrtStatus*(* RunOptionsSetRunTag)(OrtRunOptions* options, const char* run_tag);
    OrtStatus*(* RunOptionsGetRunLogVerbosityLevel)();
    OrtStatus*(* RunOptionsGetRunLogSeverityLevel)();
    OrtStatus*(* RunOptionsGetRunTag)();
    OrtStatus*(* RunOptionsSetTerminate)(OrtRunOptions* options);
    OrtStatus*(* RunOptionsUnsetTerminate)(OrtRunOptions* options);
    OrtStatus*(* CreateTensorAsOrtValue)(OrtAllocator* allocator, const int64_t* shape, size_t shape_len, ONNXTensorElementDataType type, OrtValue** out);
    OrtStatus*(* CreateTensorWithDataAsOrtValue)(const OrtMemoryInfo* info, void* p_data, size_t p_data_len, const int64_t* shape, size_t shape_len, ONNXTensorElementDataType type, OrtValue** out);
    OrtStatus*(* IsTensor)();
    OrtStatus*(* GetTensorMutableData)(OrtValue* value, void** out);
    OrtStatus*(* FillStringTensor)(OrtValue* value, const char* const* s, size_t s_len);
    OrtStatus*(* GetStringTensorDataLength)(const OrtValue* value, size_t* len);
    OrtStatus*(* GetStringTensorContent)(const OrtValue* value, void* s, size_t s_len, size_t* offsets, size_t offsets_len);
    OrtStatus*(* CastTypeInfoToTensorInfo)(const OrtTypeInfo* type_info, const OrtTensorTypeAndShapeInfo** out);
    OrtStatus*(* GetOnnxTypeFromTypeInfo)(const OrtTypeInfo* type_info, enum ONNXType* out);
    OrtStatus*(* CreateTensorTypeAndShapeInfo)();
    OrtStatus*(* SetTensorElementType)();
    OrtStatus*(* SetDimensions)();
    OrtStatus*(* GetTensorElementType)(const OrtTensorTypeAndShapeInfo* info, enum ONNXTensorElementDataType* out);
    OrtStatus*(* GetDimensionsCount)(const OrtTensorTypeAndShapeInfo* info, size_t* out);
    OrtStatus*(* GetDimensions)(const OrtTensorTypeAndShapeInfo* info, int64_t* dim_values, size_t dim_values_length);
    OrtStatus*(* GetSymbolicDimensions)(const OrtTensorTypeAndShapeInfo* info, const char* dim_params[], size_t dim_params_length);
    OrtStatus*(* GetTensorShapeElementCount)(const OrtTensorTypeAndShapeInfo* info, size_t* out);
    OrtStatus*(* GetTensorTypeAndShape)(const OrtValue* value, OrtTensorTypeAndShapeInfo** out);
    OrtStatus*(* GetTypeInfo)();
    OrtStatus*(* GetValueType)(const OrtValue* value, enum ONNXType* out);
    OrtStatus*(* CreateMemoryInfo)();
    OrtStatus*(* CreateCpuMemoryInfo)(enum OrtAllocatorType type, enum OrtMemType mem_type, OrtMemoryInfo** out);
    OrtStatus*(* CompareMemoryInfo)();
    OrtStatus*(* MemoryInfoGetName)();
    OrtStatus*(* MemoryInfoGetId)();
    OrtStatus*(* MemoryInfoGetMemType)();
    OrtStatus*(* MemoryInfoGetType)();
    OrtStatus*(* AllocatorAlloc)(OrtAllocator* ort_allocator, size_t size, void** out);
    OrtStatus*(* AllocatorFree)(OrtAllocator* ort_allocator, void* p);
    OrtStatus*(* AllocatorGetInfo)(const OrtAllocator* ort_allocator, const struct OrtMemoryInfo** out);
    OrtStatus*(* GetAllocatorWithDefaultOptions)(OrtAllocator** out);
    OrtStatus*(* AddFreeDimensionOverride)(OrtSessionOptions* options, const char* dim_denotation, int64_t dim_value);
    OrtStatus*(* GetValue)(const OrtValue* value, int index, OrtAllocator* allocator, OrtValue** out);
    OrtStatus*(* GetValueCount)(const OrtValue* value, size_t* out);
    OrtStatus*(* CreateValue)();
    OrtStatus*(* CreateOpaqueValue)();
    OrtStatus*(* GetOpaqueValue)();
    OrtStatus*(* KernelInfoGetAttribute_float)();
    OrtStatus*(* KernelInfoGetAttribute_int64)();
    OrtStatus*(* KernelInfoGetAttribute_string)();
    OrtStatus*(* KernelContext_GetInputCount)();
    OrtStatus*(* KernelContext_GetOutputCount)();
    OrtStatus*(* KernelContext_GetInput)();
    OrtStatus*(* KernelContext_GetOutput)();
    void(* ReleaseEnv)(OrtEnv* input);
    void(* ReleaseStatus)(OrtStatus* input);
    void(* ReleaseMemoryInfo)(OrtMemoryInfo* input);
    void(* ReleaseSession)(OrtSession* input);
    void(* ReleaseValue)(OrtValue* input);
    void(* ReleaseRunOptions)(OrtRunOptions* input);
    void(* ReleaseTypeInfo)(OrtTypeInfo* input);
    void(* ReleaseTensorTypeAndShapeInfo)(OrtTensorTypeAndShapeInfo* input);
    void(* ReleaseSessionOptions)(OrtSessionOptions* input);
    void(* ReleaseCustomOpDomain)();
    OrtStatus*(* GetDenotationFromTypeInfo)();
    OrtStatus*(* CastTypeInfoToMapTypeInfo)(const OrtTypeInfo* type_info, const OrtMapTypeInfo** out);
    OrtStatus*(* CastTypeInfoToSequenceTypeInfo)(const OrtTypeInfo* type_info, const OrtSequenceTypeInfo** out);
    OrtStatus*(* GetMapKeyType)(const OrtMapTypeInfo* map_type_info, enum ONNXTensorElementDataType* out);
    OrtStatus*(* GetMapValueType)(const OrtMapTypeInfo* map_type_info, OrtTypeInfo** type_info);
    OrtStatus*(* GetSequenceElementType)(const OrtSequenceTypeInfo* sequence_type_info, OrtTypeInfo** type_info);
    void(* ReleaseMapTypeInfo)(OrtMapTypeInfo* input);
    void(* ReleaseSequenceTypeInfo)(OrtSequenceTypeInfo* input);
    OrtStatus*(* SessionEndProfiling)(OrtSession* session, OrtAllocator* allocator, char** out);
    OrtStatus*(* SessionGetModelMetadata)(const OrtSession* session, OrtModelMetadata** out);
    OrtStatus*(* ModelMetadataGetProducerName)(const OrtModelMetadata* model_metadata, OrtAllocator* allocator, char** value);
    OrtStatus*(* ModelMetadataGetGraphName)(const OrtModelMetadata* model_metadata, OrtAllocator* allocator, char** value);
    OrtStatus*(* ModelMetadataGetDomain)(const OrtModelMetadata* model_metadata, OrtAllocator* allocator, char** value);
    OrtStatus*(* ModelMetadataGetDescription)(const OrtModelMetadata* model_metadata, OrtAllocator* allocator, char** value);
    OrtStatus*(* ModelMetadataLookupCustomMetadataMap)(const OrtModelMetadata* model_metadata, OrtAllocator* allocator, const char* key, char** value);
    OrtStatus*(* ModelMetadataGetVersion)(const OrtModelMetadata* model_metadata, int64_t* value);
    void(* ReleaseModelMetadata)(OrtModelMetadata* input);
    OrtStatus*(* CreateEnvWithGlobalThreadPools)();
    OrtStatus*(* DisablePerSessionThreads)();
    OrtStatus*(* CreateThreadingOptions)();
    void(* ReleaseThreadingOptions)(OrtThreadingOptions* input);
    OrtStatus*(* ModelMetadataGetCustomMetadataMapKeys)(const OrtModelMetadata* model_metadata, OrtAllocator* allocator, char*** keys, int64_t* num_keys);
    OrtStatus*(* AddFreeDimensionOverrideByName)(OrtSessionOptions* options, const char* dim_name, int64_t dim_value);
    OrtStatus*(* GetAvailableProviders)(char*** out_ptr, int* provider_length);
    OrtStatus*(* ReleaseAvailableProviders)(char** ptr, int providers_length);
    OrtStatus*(* GetStringTensorElementLength)();
    OrtStatus*(* GetStringTensorElement)();
    OrtStatus*(* FillStringTensorElement)();
    OrtStatus*(* AddSessionConfigEntry)(OrtSessionOptions* options, const char* config_key, const char* config_value);
    OrtStatus*(* CreateAllocator)();
    void(* ReleaseAllocator)(OrtAllocator* input);
    OrtStatus*(* RunWithBinding)();
    OrtStatus*(* CreateIoBinding)();
    void(* ReleaseIoBinding)(OrtIoBinding* input);
    OrtStatus*(* BindInput)();
    OrtStatus*(* BindOutput)();
    OrtStatus*(* BindOutputToDevice)();
    OrtStatus*(* GetBoundOutputNames)();
    OrtStatus*(* GetBoundOutputValues)();
    void(* ClearBoundInputs)();
    void(* ClearBoundOutputs)();
    OrtStatus*(* TensorAt)();
    OrtStatus*(* CreateAndRegisterAllocator)();
    OrtStatus*(* SetLanguageProjection)();
    OrtStatus*(* SessionGetProfilingStartTimeNs)();
    OrtStatus*(* SetGlobalIntraOpNumThreads)();
    OrtStatus*(* SetGlobalInterOpNumThreads)();
    OrtStatus*(* SetGlobalSpinControl)();
    OrtStatus*(* AddInitializer)();
    OrtStatus*(* CreateEnvWithCustomLoggerAndGlobalThreadPools)();


    OrtStatus*(* SetGlobalDenormalAsZero)();
    OrtStatus*(* CreateArenaCfg)();
    void(* ReleaseArenaCfg)(OrtArenaCfg* input);
    OrtStatus*(* ModelMetadataGetGraphDescription)(const OrtModelMetadata* model_metadata, OrtAllocator* allocator, char** value);
    OrtStatus*(* SessionOptionsAppendExecutionProvider_TensorRT)();
    OrtStatus*(* SetCurrentGpuDeviceId)();
    OrtStatus*(* GetCurrentGpuDeviceId)();
    OrtStatus*(* KernelInfoGetAttributeArray_float)();
    OrtStatus*(* KernelInfoGetAttributeArray_int64)();
    OrtStatus*(* CreateArenaCfgV2)();
    OrtStatus*(* AddRunConfigEntry)();
    OrtStatus*(* CreatePrepackedWeightsContainer)();
    void(* PrepackedWeightsContainer)(OrtPrepackedWeightsContainer* input);
    OrtStatus*(* CreateSessionWithPrepackedWeightsContainer)();
    OrtStatus*(* CreateSessionFromArrayWithPrepackedWeightsContainer)();
    OrtStatus*(* SessionOptionsAppendExecutionProvider_TensorRT_V2)();
    OrtStatus*(* CreateTensorRTProviderOptions)();
    OrtStatus*(* UpdateTensorRTProviderOptions)();
    OrtStatus*(* GetTensorRTProviderOptionsAsString)();
    void(* ReleaseTensorRTProviderOptions)(OrtTensorRTProviderOptionsV2* input);
    OrtStatus*(* EnableOrtCustomOps)();
    OrtStatus*(* RegisterAllocator)();
    OrtStatus*(* UnregisterAllocator)();
    OrtStatus*(* IsSparseTensor)();
    OrtStatus*(* CreateSparseTensorAsOrtValue)();
    OrtStatus*(* FillSparseTensorCoo)();
    OrtStatus*(* FillSparseTensorCsr)();
    OrtStatus*(* FillSparseTensorBlockSparse)();
    OrtStatus*(* CreateSparseTensorWithValuesAsOrtValue)();
    OrtStatus*(* UseCooIndices)();
    OrtStatus*(* UseCsrIndices)();
    OrtStatus*(* UseBlockSparseIndices)();
    OrtStatus*(* GetSparseTensorFormat)();
    OrtStatus*(* GetSparseTensorValuesTypeAndShape)();
    OrtStatus*(* GetSparseTensorValues)();
    OrtStatus*(* GetSparseTensorIndicesTypeShape)();
    OrtStatus*(* GetSparseTensorIndices)();
    OrtStatus*(* HasValue)();
    OrtStatus*(* KernelContext_GetGPUComputeStream)();
    OrtStatus*(* GetTensorMemoryInfo)();
    OrtStatus*(* GetExecutionProviderApi)();
    OrtStatus*(* SessionOptionsSetCustomCreateThreadFn)();
    OrtStatus*(* SessionOptionsSetCustomThreadCreationOptions)();
    OrtStatus*(* SessionOptionsSetCustomJoinThreadFn)();
    OrtStatus*(* SetGlobalCustomCreateThreadFn)();
    OrtStatus*(* SetGlobalCustomThreadCreationOptions)();
    OrtStatus*(* SetGlobalCustomJoinThreadFn)();
    OrtStatus*(* SynchronizeBoundInputs)();
    OrtStatus*(* SynchronizeBoundOutputs)();
};