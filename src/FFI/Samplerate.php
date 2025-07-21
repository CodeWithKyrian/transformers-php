<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

use Exception;
use FFI;
use FFI\CData;
use FFI\CType;
use RuntimeException;

class Samplerate extends NativeLibrary
{
    /**
     * Get the header file name for this library
     * 
     * @return string The header file name
     */
    protected function getHeaderName(): string
    {
        return 'samplerate';
    }

    /**
     * Get the library file name (without extension) for this library
     * 
     * @return string The library file name
     */
    protected function getLibraryName(): string
    {
        // Return only the base name
        return 'samplerate';
    }

    /**
     * Get the library version string for this library
     * 
     * @return string The library version
     */
    protected function getLibraryVersion(): string
    {
        return '0.2.2';
    }

    /**
     * Creates a new sample rate converter.
     *
     * @param int $converter_type The type of converter to create.
     * @param int $channels The number of channels.
     *
     * @return CData|null The sample rate converter, or null if creation failed.
     * @throws Exception
     */
    public function src_new(int $converter_type, int $channels): ?CData
    {
        $error = $this->new('int32_t');
        
        $state = $this->ffi->{'src_new'}($converter_type, $channels, \FFI::addr($error));

        if ($error->cdata !== 0) {
            throw new RuntimeException($this->strerror($error->cdata));
        }
        
        return $state;
    }

    /**
     * Deletes a sample rate converter.
     *
     * @param CData $state The sample rate converter to delete.
     *
     * @return void
     * @throws Exception
     */
    public function delete(CData $state): void
    {
        $this->ffi->{'src_delete'}($state);
    }

    /**
     * Processes a block of audio data.
     *
     * @param CData $state The sample rate converter.
     * @param CData $data The data to process.
     *
     * @return int 0 on success, or an error code.
     * @throws Exception
     */
    public function process(CData $state, CData $data): int
    {
        return $this->ffi->{'src_process'}($state, $data);
    }

    /**
     * Resets a sample rate converter.
     *
     * @param CData $state The sample rate converter to reset.
     *
     * @return int 0 on success, or an error code.
     * @throws Exception
     */
    public function reset(CData $state): int
    {
        return $this->ffi->{'src_reset'}($state);
    }

    /**
     * Gets the error message for an error code.
     *
     * @param int $error The error code.
     *
     * @return string The error message.
     * @throws Exception
     */
    public function strerror(int $error): string
    {
        return $this->ffi->{'src_strerror'}($error);
    }

    /**
     * Gets the version of the library.
     *
     * @return string The version of the library.
     * @throws Exception
     */
    public function version(): string
    {
        return $this->ffi->{'src_get_version'}();
    }

    /**
     * Performs a simple sample rate conversion.
     *
     * @param int $converter_type The type of converter to use.
     * @param int $channels The number of channels.
     * @param float $output_rate The output sample rate.
     * @param float $input_rate The input sample rate.
     * @param CData $input The input data.
     * @param int $input_frames The number of input frames.
     * @param int $output_frames_max The maximum number of output frames.
     *
     * @return array An array containing the output data and the number of output frames.
     * @throws Exception
     */
    public function simple(
        int $converter_type,
        int $channels,
        float $output_rate,
        float $input_rate,
        CData $input,
        int $input_frames,
        int $output_frames_max
    ): array {
        $output = $this->new("float[$output_frames_max]");
        $output_frames = $this->new('int');
        $output_frames->cdata = $output_frames_max;
        
        $error = $this->ffi->{'src_simple'}(
            $output,
            $input,
            $input_frames,
            $output_frames,
            $output_rate / $input_rate,
            $converter_type,
            $channels
        );
        
        if ($error !== 0) {
            throw new RuntimeException($this->strerror($error));
        }
        
        return [$output, $output_frames->cdata];
    }
}
