#ifndef RINDOW_MATLIB_H_
#define RINDOW_MATLIB_H_

#include <stdint.h>

enum rindow_matlib_dtype {
    rindow_matlib_dtype_unknown   = 0,
    rindow_matlib_dtype_bool      = 1,
    rindow_matlib_dtype_int8      = 2,
    rindow_matlib_dtype_int16     = 3,
    rindow_matlib_dtype_int32     = 4,
    rindow_matlib_dtype_int64     = 5,
    rindow_matlib_dtype_uint8     = 6,
    rindow_matlib_dtype_uint16    = 7,
    rindow_matlib_dtype_uint32    = 8,
    rindow_matlib_dtype_uint64    = 9,
    rindow_matlib_dtype_float8    = 10,
    rindow_matlib_dtype_float16   = 11,
    rindow_matlib_dtype_float32   = 12,
    rindow_matlib_dtype_float64   = 13,
    rindow_matlib_dtype_complex16 = 14,
    rindow_matlib_dtype_complex32 = 15,
    rindow_matlib_dtype_complex64 = 16,
    rindow_matlib_dtype_complex128 = 17
};
 

#if _MSC_VER
  #if !defined(RINDOW_FUNC)
    #if defined(RINDOW_COMPILING_DLL)
      #define RINDOW_FUNC
      #define RINDOW_FUNC_DECL extern __declspec(dllexport)
    #elif defined(RINDOW_MATLIB_INCLUDING_SOURCE)
      #define RINDOW_FUNC
      #define RINDOW_FUNC_DECL
    #else
      #define RINDOW_FUNC
      #define RINDOW_FUNC_DECL extern __declspec(dllimport)
    #endif
  #endif
#else // _MSC_VER
  #define RINDOW_FUNC
  #define RINDOW_FUNC_DECL extern
#endif // _MSC_VER


#define RINDOW_MATLIB_SUCCESS                 0
#define RINDOW_MATLIB_E_MEM_ALLOC_FAILURE     -101
#define RINDOW_MATLIB_E_PERM_OUT_OF_RANGE     -102
#define RINDOW_MATLIB_E_DUP_AXIS              -103
#define RINDOW_MATLIB_E_UNSUPPORTED_DATA_TYPE -104
#define RINDOW_MATLIB_E_UNMATCH_IMAGE_BUFFER_SIZE -105
#define RINDOW_MATLIB_E_UNMATCH_COLS_BUFFER_SIZE -106
#define RINDOW_MATLIB_E_INVALID_SHAPE_OR_PARAM -107
#define RINDOW_MATLIB_E_IMAGES_OUT_OF_RANGE   -108
#define RINDOW_MATLIB_E_COLS_OUT_OF_RANGE     -109

#define RINDOW_MATLIB_NO_TRANS       111
#define RINDOW_MATLIB_TRANS          112
//#define RINDOW_MATLIB_CONJ_TRANS     113
//#define RINDOW_MATLIB_CONJ_NO_TRANS  114

// Matlib is compiled for sequential use
#define RINDOW_MATLIB_SEQUENTIAL 0
// Matlib is compiled using normal threading model
#define RINDOW_MATLIB_THREAD     1
// Matlib is compiled using OpenMP threading model
#define RINDOW_MATLIB_OPENMP     2


static inline int32_t rindow_matlib_common_dtype_to_valuesize(int32_t dtype)
{
    switch (dtype) {
        case rindow_matlib_dtype_bool:
        case rindow_matlib_dtype_int8:
        case rindow_matlib_dtype_uint8:
        case rindow_matlib_dtype_float8:
            return 1;
        case rindow_matlib_dtype_int16:
        case rindow_matlib_dtype_uint16:
        case rindow_matlib_dtype_float16:
        case rindow_matlib_dtype_complex16:
            return 2;
        case rindow_matlib_dtype_int32:
        case rindow_matlib_dtype_uint32:
        case rindow_matlib_dtype_float32:
        case rindow_matlib_dtype_complex32:
            return 4;
        case rindow_matlib_dtype_int64:
        case rindow_matlib_dtype_uint64:
        case rindow_matlib_dtype_float64:
        case rindow_matlib_dtype_complex64:
            return 8;
        case rindow_matlib_dtype_complex128:
            return 16;
    }
    return 0;
}

static inline int32_t rindow_matlib_common_dtype_is_int(int32_t dtype)
{
    switch (dtype) {
        case rindow_matlib_dtype_int8:
        case rindow_matlib_dtype_uint8:
        case rindow_matlib_dtype_int16:
        case rindow_matlib_dtype_uint16:
        case rindow_matlib_dtype_int32:
        case rindow_matlib_dtype_uint32:
        case rindow_matlib_dtype_int64:
        case rindow_matlib_dtype_uint64:
            return 1;
    }
    return 0;
}

static inline int32_t rindow_matlib_common_dtype_is_float(int32_t dtype)
{
    switch (dtype) {
        case rindow_matlib_dtype_float8:
        case rindow_matlib_dtype_float16:
        case rindow_matlib_dtype_float32:
        case rindow_matlib_dtype_float64:
            return 1;
    }
    return 0;
}

static inline int32_t rindow_matlib_common_dtype_is_complex(int32_t dtype)
{
    switch (dtype) {
        case rindow_matlib_dtype_complex16:
        case rindow_matlib_dtype_complex32:
        case rindow_matlib_dtype_complex64:
        case rindow_matlib_dtype_complex128:
            return 1;
    }
    return 0;
}

static inline int32_t rindow_matlib_common_dtype_is_bool(int32_t dtype)
{
    switch (dtype) {
        case rindow_matlib_dtype_bool:
            return 1;
    }
    return 0;
}


#ifdef __cplusplus
extern "C" {
#endif

RINDOW_FUNC_DECL int32_t rindow_matlib_common_get_nprocs(void);
RINDOW_FUNC_DECL int32_t rindow_matlib_common_get_num_threads(void);
RINDOW_FUNC_DECL int32_t rindow_matlib_common_get_parallel(void);
RINDOW_FUNC_DECL char* rindow_matlib_common_get_version(void);

RINDOW_FUNC_DECL void* rindow_matlib_common_get_address(int32_t dtype, void *buffer, int32_t offset);

RINDOW_FUNC_DECL float rindow_matlib_s_sum(int32_t n,float *x,int32_t incX);
RINDOW_FUNC_DECL double rindow_matlib_d_sum(int32_t n,double *x,int32_t incX);
RINDOW_FUNC_DECL int64_t rindow_matlib_i_sum(int32_t dtype, int32_t n,void *x,int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_s_imax(int32_t n,float *x, int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_d_imax(int32_t n,double *x, int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_i_imax(int32_t dtype, int32_t n,void *x, int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_s_imin(int32_t n,float *x, int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_d_imin(int32_t n,double *x, int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_i_imin(int32_t dtype, int32_t n,void *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_increment(int32_t n, float *x, int32_t incX, float alpha, float beta);
RINDOW_FUNC_DECL void rindow_matlib_d_increment(int32_t n, double *x, int32_t incX, double alpha, double beta);
RINDOW_FUNC_DECL void rindow_matlib_s_reciprocal(int32_t n, float *x, int32_t incX, float alpha, float beta);
RINDOW_FUNC_DECL void rindow_matlib_d_reciprocal(int32_t n, double *x, int32_t incX, double alpha, double beta);
RINDOW_FUNC_DECL void rindow_matlib_s_maximum(int32_t m,int32_t n,float *a, int32_t ldA,float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_maximum(int32_t m,int32_t n,double *a, int32_t ldA,double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_minimum(int32_t m,int32_t n,float *a, int32_t ldA,float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_minimum(int32_t m,int32_t n,double *a, int32_t ldA,double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_greater(int32_t m,int32_t n,float *a, int32_t ldA,float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_greater(int32_t m,int32_t n,double *a, int32_t ldA,double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_greater_equal(int32_t m,int32_t n,float *a, int32_t ldA,float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_greater_equal(int32_t m,int32_t n,double *a, int32_t ldA,double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_less(int32_t m,int32_t n,float *a, int32_t ldA,float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_less(int32_t m,int32_t n,double *a, int32_t ldA,double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_less_equal(int32_t m,int32_t n,float *a, int32_t ldA,float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_less_equal(int32_t m,int32_t n,double *a, int32_t ldA,double *x, int32_t incX);

RINDOW_FUNC_DECL void rindow_matlib_s_multiply(int32_t trans,int32_t m,int32_t n,float *x, int32_t incX,float *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_d_multiply(int32_t trans,int32_t m,int32_t n,double *x, int32_t incX,double *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_s_add(int32_t trans,int32_t m,int32_t n,float alpha,float *x, int32_t incX,float *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_d_add(int32_t trans,int32_t m,int32_t n,double alpha,double *x, int32_t incX,double *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_s_duplicate(int32_t trans,int32_t m,int32_t n,float *x, int32_t incX,float *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_d_duplicate(int32_t trans,int32_t m,int32_t n,double *x, int32_t incX,double *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_s_square(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_square(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_sqrt(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_sqrt(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_rsqrt(int32_t n, float alpha, float *x, int32_t incX, float beta);
RINDOW_FUNC_DECL void rindow_matlib_d_rsqrt(int32_t n, double alpha, double *x, int32_t incX, double beta);
RINDOW_FUNC_DECL void rindow_matlib_s_pow(int32_t trans,int32_t m,int32_t n,float *a, int32_t ldA,float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_pow(int32_t trans,int32_t m,int32_t n,double *a, int32_t ldA,double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_exp(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_exp(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_log(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_log(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_tanh(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_tanh(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_sin(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_sin(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_cos(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_cos(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_tan(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_tan(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_zeros(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_zeros(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_i_zeros(int32_t dtype, int32_t n,void *x, int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_s_onehot(int32_t dtype, int32_t m, int32_t n, void *x, int32_t incX, float alpha, float *a, int32_t ldA);
RINDOW_FUNC_DECL int32_t rindow_matlib_d_onehot(int32_t dtype, int32_t m, int32_t n, void *x, int32_t incX, double alpha, double *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_s_softmax(int32_t m, int32_t n, float *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_d_softmax(int32_t m, int32_t n, double *a, int32_t ldA);
RINDOW_FUNC_DECL void rindow_matlib_s_equal(int32_t n, float *x, int32_t incX, float *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_d_equal(int32_t n, double *x, int32_t incX, double *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_i_equal(int32_t dtype, int32_t n, void *x, int32_t incX, void *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_s_notequal(int32_t n, float *x, int32_t incX, float *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_d_notequal(int32_t n, double *x, int32_t incX, double *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_i_notequal(int32_t dtype, int32_t n, void *x, int32_t incX, void *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_s_not(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_not(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_i_not(int32_t dtype, int32_t n, void *x, int32_t incX);
RINDOW_FUNC_DECL int32_t rindow_matlib_astype(int32_t n, int32_t from_dtype, void *x, int32_t incX, int32_t to_dtype, void *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_s_matrixcopy(int32_t trans, int32_t m, int32_t n, float alpha,float *a, int32_t ldA, float *b, int32_t ldB);
RINDOW_FUNC_DECL void rindow_matlib_d_matrixcopy(int32_t trans, int32_t m, int32_t n, double alpha,double *a, int32_t ldA, double *b, int32_t ldB);
RINDOW_FUNC_DECL void rindow_matlib_s_imagecopy(int32_t height,int32_t width,int32_t channels,float *a,float *b,
    int32_t channelsFirst,int32_t heightShift,int32_t widthShift,int32_t verticalFlip,int32_t horizontalFlip,int32_t rgbFlip);
RINDOW_FUNC_DECL void rindow_matlib_d_imagecopy(int32_t height,int32_t width,int32_t channels,double *a,double *b,
    int32_t channelsFirst,int32_t heightShift,int32_t widthShift,int32_t verticalFlip,int32_t horizontalFlip,int32_t rgbFlip);
RINDOW_FUNC_DECL void rindow_matlib_i8_imagecopy(int32_t height,int32_t width,int32_t channels,uint8_t *a,uint8_t *b,
    int32_t channelsFirst,int32_t heightShift,int32_t widthShift,int32_t verticalFlip,int32_t horizontalFlip,int32_t rgbFlip);
RINDOW_FUNC_DECL void rindow_matlib_fill(int32_t dtype, int32_t n, void *value, void *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_nan2num(int32_t n, float *x, int32_t incX, float alpha);
RINDOW_FUNC_DECL void rindow_matlib_d_nan2num(int32_t n, double *x, int32_t incX, double alpha);
RINDOW_FUNC_DECL void rindow_matlib_s_isnan(int32_t n, float *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_d_isnan(int32_t n, double *x, int32_t incX);
RINDOW_FUNC_DECL void rindow_matlib_s_searchsorted(int32_t m, int32_t n, float *a, int32_t ldA, float *x, int32_t incX,
    int32_t right, int32_t dtype, void *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_d_searchsorted(int32_t m, int32_t n, double *a, int32_t ldA, double *x, int32_t incX,
    int32_t right, int32_t dtype, void *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_s_cumsum(int32_t n,float *x, int32_t incX,int32_t exclusive,int32_t reverse,float *y, int32_t incY);
RINDOW_FUNC_DECL void rindow_matlib_d_cumsum(int32_t n,double *x, int32_t incX,int32_t exclusive,int32_t reverse,double *y, int32_t incY);

RINDOW_FUNC_DECL int32_t rindow_matlib_s_transpose(int32_t ndim,int32_t *shape,int32_t *perm,float *a,float *b);
RINDOW_FUNC_DECL int32_t rindow_matlib_d_transpose(int32_t ndim,int32_t *shape,int32_t *perm,double *a,double *b);
RINDOW_FUNC_DECL int32_t rindow_matlib_i_transpose(int32_t dtype,int32_t ndim,int32_t *shape,int32_t *perm,void *a,void *b);
RINDOW_FUNC_DECL void rindow_matlib_s_bandpart(int32_t m, int32_t n, int32_t k,float *a,int32_t lower, int32_t upper);
RINDOW_FUNC_DECL void rindow_matlib_d_bandpart(int32_t m, int32_t n, int32_t k,double *a,int32_t lower, int32_t upper);

RINDOW_FUNC_DECL int32_t rindow_matlib_s_gather(int32_t reverse,int32_t addMode,int32_t n,int32_t k,int32_t numClass,int32_t dtype,void *x,float *a,float *b);
RINDOW_FUNC_DECL int32_t rindow_matlib_d_gather(int32_t reverse,int32_t addMode,int32_t n,int32_t k,int32_t numClass,int32_t dtype,void *x,double *a,double *b);
RINDOW_FUNC_DECL int32_t rindow_matlib_i_gather(int32_t reverse,int32_t addMode,int32_t n,int32_t k,int32_t numClass,int32_t dtype,void *x,int32_t data_dtype,void *a,void *b);
RINDOW_FUNC_DECL int32_t rindow_matlib_s_reducegather(int32_t reverse,int32_t addMode,int32_t m,int32_t n,int32_t numClass,int32_t dtype,void *x,float *a,float *b);
RINDOW_FUNC_DECL int32_t rindow_matlib_d_reducegather(int32_t reverse,int32_t addMode,int32_t m,int32_t n,int32_t numClass,int32_t dtype,void *x,double *a,double *b);
RINDOW_FUNC_DECL int32_t rindow_matlib_i_reducegather(int32_t reverse,int32_t addMode,int32_t m,int32_t n,int32_t numClass,int32_t dtype,void *x,int32_t data_dtype,void *a,void *b);

RINDOW_FUNC_DECL void rindow_matlib_s_slice(int32_t reverse,int32_t addMode,int32_t m,int32_t n,int32_t k,int32_t size,float *a, int32_t incA,float *y, int32_t incY,int32_t startAxis0,int32_t sizeAxis0,int32_t startAxis1,int32_t sizeAxis1,int32_t startAxis2,int32_t sizeAxis2);
RINDOW_FUNC_DECL void rindow_matlib_d_slice(int32_t reverse,int32_t addMode,int32_t m,int32_t n,int32_t k,int32_t size,double *a, int32_t incA,double *y, int32_t incY,int32_t startAxis0,int32_t sizeAxis0,int32_t startAxis1,int32_t sizeAxis1,int32_t startAxis2,int32_t sizeAxis2);
RINDOW_FUNC_DECL void rindow_matlib_i_slice(int32_t reverse,int32_t addMode,int32_t m,int32_t n,int32_t k,int32_t size,int32_t dtype,void *a, int32_t incA,void *y, int32_t incY,int32_t startAxis0,int32_t sizeAxis0,int32_t startAxis1,int32_t sizeAxis1,int32_t startAxis2,int32_t sizeAxis2);

RINDOW_FUNC_DECL void rindow_matlib_s_repeat(int32_t m,int32_t k,int32_t repeats,float *a,float *b);
RINDOW_FUNC_DECL void rindow_matlib_d_repeat(int32_t m,int32_t k,int32_t repeats,double *a,double *b);

RINDOW_FUNC_DECL void rindow_matlib_s_reducesum(int32_t m,int32_t n,int32_t k,float *a,float *b);
RINDOW_FUNC_DECL void rindow_matlib_d_reducesum(int32_t m,int32_t n,int32_t k,double *a,double *b);
RINDOW_FUNC_DECL void rindow_matlib_s_reducemax(int32_t m,int32_t n,int32_t k,float *a,float *b);
RINDOW_FUNC_DECL void rindow_matlib_d_reducemax(int32_t m,int32_t n,int32_t k,double *a,double *b);
RINDOW_FUNC_DECL void rindow_matlib_s_reduceargmax(int32_t m,int32_t n,int32_t k,float *a,int32_t dtype,void *b);
RINDOW_FUNC_DECL void rindow_matlib_d_reduceargmax(int32_t m,int32_t n,int32_t k,double *a,int32_t dtype,void *b);

RINDOW_FUNC_DECL void rindow_matlib_s_randomuniform(int32_t n,float *x, int32_t incX,float low,float high,int32_t seed);
RINDOW_FUNC_DECL void rindow_matlib_d_randomuniform(int32_t n,double *x, int32_t incX,double low,double high,int32_t seed);
RINDOW_FUNC_DECL void rindow_matlib_i_randomuniform(int32_t n,int32_t dtype,void *x, int32_t incX,int32_t low,int32_t high,int32_t seed);
RINDOW_FUNC_DECL void rindow_matlib_s_randomnormal(int32_t n,float *x, int32_t incX,float mean,float scale,int32_t seed);
RINDOW_FUNC_DECL void rindow_matlib_d_randomnormal(int32_t n,double *x, int32_t incX,double mean,double scale,int32_t seed);
RINDOW_FUNC_DECL void rindow_matlib_i_randomsequence(int32_t n,int32_t size,int32_t dtype,void *x, int32_t incX,int32_t seed);

RINDOW_FUNC_DECL int32_t rindow_matlib_im2col1d(
    int32_t dtype,int32_t reverse,
    void *images_data,
    int32_t images_size,
    int32_t batches,
    int32_t im_w,
    int32_t channels,
    int32_t filter_w,
    int32_t stride_w,
    int32_t padding,int32_t channels_first,
    int32_t dilation_w,
    int32_t cols_channels_first,
    void *cols_data,int32_t cols_size
    );

RINDOW_FUNC_DECL int32_t rindow_matlib_im2col2d(
    int32_t dtype,int32_t reverse,
    void *images_data,int32_t images_size,
    int32_t batches,
    int32_t im_h,int32_t im_w,
    int32_t channels,
    int32_t filter_h,int32_t filter_w,
    int32_t stride_h,int32_t stride_w,
    int32_t padding,int32_t channels_first,
    int32_t dilation_h,int32_t dilation_w,
    int32_t cols_channels_first,
    void *cols_data,int32_t cols_size
    );

RINDOW_FUNC_DECL int32_t rindow_matlib_im2col3d(
    int32_t dtype,int32_t reverse,
    void* images_data,int32_t images_size,
    int32_t batches,
    int32_t im_d,int32_t im_h,int32_t im_w,
    int32_t channels,
    int32_t filter_d,int32_t filter_h,int32_t filter_w,
    int32_t stride_d,int32_t stride_h,int32_t stride_w,
    int32_t padding,int32_t channels_first,
    int32_t dilation_d,int32_t dilation_h,int32_t dilation_w,
    int32_t cols_channels_first,
    void* cols_data,int32_t cols_size
    );

#ifdef __cplusplus
} // extern "C"
#endif

// RINDOW_MATLIB_H_
#endif

