<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI;

use Exception;
use FFI\CData;
use RuntimeException;

class Sndfile extends NativeLibrary
{
    /**
     * Get the header file name for this library
     * 
     * @return string The header file name
     */
    protected function getHeaderName(): string
    {
        return 'sndfile';
    }

    /**
     * Get the library file name (without extension) for this library
     * 
     * @return string The library file name
     */
    protected function getLibraryName(): string
    {
        return 'libsndfile';
    }


    /**
     * Opens a sound file for reading.
     *
     * @param string $path The path to the sound file.
     * @param int $mode The mode to open the file in.
     * @param CData|null $info The info struct to fill.
     *
     * @return CData|null The SNDFILE handle, or null if the file could not be opened.
     * @throws Exception
     */
    public function open(string $path, int $mode, ?CData $info = null): ?CData
    {
        $info ??= $this->new('SF_INFO');
        $handle = $this->ffi->{'sf_open'}($path, $mode, $info);

        if ($handle === null) {
            throw new RuntimeException($this->ffi->{'sf_strerror'}(null));
        }

        return $handle;
    }

    /**
     * Reads frames from a sound file.
     *
     * @param CData $handle The SNDFILE handle.
     * @param CData $buffer The buffer to read into.
     * @param int $frames The number of frames to read.
     *
     * @return int The number of frames read.
     * @throws Exception
     */
    public function readf_float(CData $handle, CData $buffer, int $frames): int
    {
        return $this->ffi->{'sf_readf_float'}($handle, $buffer, $frames);
    }

    /** 
     * Writes frames to a sound file.
     *
     * @param CData $handle The SNDFILE handle.
     * @param CData $buffer The buffer to write.
     * @param int $frames The number of frames to write.
     *
     * @return int The number of frames written.
     * @throws Exception
     */
    public function writef_float(CData $handle, CData $buffer, int $frames): int
    {
        return $this->ffi->{'sf_writef_float'}($handle, $buffer, $frames);
    }

    /**
     * Closes a sound file.
     *
     * @param CData $handle The SNDFILE handle.
     *
     * @return int 0 on success, or an error code.
     * @throws Exception
     */
    public function close(CData $handle): int
    {
        return $this->ffi->{'sf_close'}($handle);
    }

    /**
     * Gets the error message for the last error.
     *
     * @param CData|null $handle The SNDFILE handle, or null for the last error.
     *
     * @return string The error message.
     * @throws Exception
     */
    public function strerror(?CData $handle = null): string
    {
        return $this->ffi->{'sf_strerror'}($handle);
    }

    /**
     * Seeks to a position in a sound file.
     *
     * @param CData $handle The SNDFILE handle.
     * @param int $frames The frame offset.
     * @param int $whence The seek mode.
     *
     * @return int The new position.
     * @throws Exception
     */
    public function seek(CData $handle, int $frames, int $whence): int
    {
        return $this->ffi->{'sf_seek'}($handle, $frames, $whence);
    }
}
