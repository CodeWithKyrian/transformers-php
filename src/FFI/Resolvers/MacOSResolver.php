<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI\Resolvers;

class MacOSResolver extends Resolver
{
    protected string $extension = 'dylib';

    protected array $libDirectories = [
        ['directory' => '/opt/homebrew/lib', 'priority' => 0],
        ['directory' => '/usr/local/lib', 'priority' => 1],
        ['directory' => '/usr/lib', 'priority' => 3],
        ['directory' => '/Library/Frameworks', 'priority' => 4],
    ];

    public function inferInstaller(): ?string
    {
        return match (true) {
            $this->commandExists('brew') => 'brew',
            $this->commandExists('port') => 'port',
            default => 'cli',
        };
    }


    protected function commandExists(string $command): bool
    {
        return shell_exec('command -v '.escapeshellarg($command)) !== '';
    }
}