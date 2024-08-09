<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI\Resolvers;

use Exception;
use RuntimeException;

abstract class Resolver
{
    /**
     * The extension of the resolver
     */
    protected string $extension = '';

    /**
     * The directories where the libraries are located arranged by priority.
     */
    protected array $libDirectories = [];


    /**
     * Adds a library directory to the list of directories.
     *
     * @param string $directory The path to the directory.
     * @param int $priority The priority of the directory. Default is 0.
     *
     * @return void
     */
    public function addLibDirectory(string $directory, int $priority = 0): void
    {
        $this->libDirectories[] = ['directory' => $directory, 'priority' => $priority];

        usort($this->libDirectories, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * Removes a library directory from the list of directories.
     *
     * @param string $directory The path to the directory.
     *
     * @return void
     */
    public function removeLibDirectory(string $directory): void
    {
        $this->libDirectories = array_filter($this->libDirectories, function ($libDirectory) use ($directory) {
            return $libDirectory['directory'] !== $directory;
        });
    }

    /**
     * Resolves the file path of a library based on the given name and ABI.
     *
     * @param string $name The name of the library.
     * @param ?string $abi The ABI of the library. Default is null.
     *
     * @return ?string The file path of the library if found, otherwise null.
     */
    public function resolve(string $name, string $abi = null): ?string
    {
        foreach ($this->libDirectories as $libDirectory) {
            $filename = $abi ? "$name.$abi.$this->extension" : "$name.$this->extension";
            $filepath = rtrim($libDirectory['directory'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

            if (file_exists($filepath)) return $filepath;
        }
        return null;
    }

    /**
     * Checks if a library with the given name and ABI exists.
     */
    public function exists(string $name, string $abi = null): bool
    {
        return $this->resolve($name, $abi) !== null;
    }


    /**
     * Factory method that creates a Resolver instance based on the current operating system.
     *
     * @return Resolver The Resolver instance for the current operating system.
     * @throws RuntimeException If the operating system is not supported.
     */
    public static function factory(): Resolver
    {
        return match (PHP_OS_FAMILY) {
            'Linux' => new LinuxResolver(),
            'Darwin' => new MacOSResolver(),
            'Windows' => new WindowsResolver(),
            default => throw new RuntimeException('Unsupported OS')
        };
    }

    /**
     * Infer the installer for the current operating system.
     */
    abstract public function inferInstaller(): ?string;
}