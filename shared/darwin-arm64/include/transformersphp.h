#ifndef FAST_TRANSFORMERS_UTILS_H
#define FAST_TRANSFORMERS_UTILS_H

enum {
    LOG_MEL_NONE = 0,
    LOG_MEL_LOG = 1,
    LOG_MEL_LOG10 = 2,
    LOG_MEL_DB = 3
};

void pad_reflect(float *input, int length, float *padded, int padded_length);

void spectrogram(
    float *waveform, int waveform_length, float *spectrogram, int spectrogram_length, int hop_length, int fft_length, 
    float *window,int window_length, int d1, int d1_max, float power, int center, float preemphasis, float *mel_filters, 
    int num_mel_filters, int num_frequency_bins, float mel_floor, int log_mel, int remove_dc_offset, int do_pad, int transpose
);

#endif // FAST_TRANSFORMERS_UTILS_H
