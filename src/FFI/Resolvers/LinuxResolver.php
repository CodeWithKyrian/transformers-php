<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI\Resolvers;

class LinuxResolver extends Resolver
{

    protected string $extension = 'so';

    protected array $libDirectories = [
        ['directory' => '/usr/local/lib', 'priority' => 0],
        ['directory' => '/usr/lib', 'priority' => 1],
        ['directory' => '/lib', 'priority' => 10],
    ];

    public function __construct()
    {
        $is64bit = PHP_INT_SIZE === 8;

        if ($is64bit) {
            $this->addLibDirectory('/usr/local/lib/x86_64-linux-gnu', 2);
            $this->addLibDirectory('/lib/x86_64-linux-gnu', 3);
            $this->addLibDirectory('/usr/lib/x86_64-linux-gnu', 4);
        } else {
            $this->addLibDirectory('/usr/local/lib/i386-linux-gnu', 2);
            $this->addLibDirectory('/lib/i386-linux-gnu', 3);
            $this->addLibDirectory('/usr/lib/i386-linux-gnu', 4);
        }
    }

    public function inferInstaller(): ?string
    {
        return match (true) {
            $this->commandExists('apt-get') => 'apt-get',
            $this->commandExists('apk') => 'apk',
            $this->commandExists('yum') => 'yum',
            $this->commandExists('dnf') => 'dnf',
            default => 'cli',
        };
    }

    protected function commandExists(string $command): bool
    {
        return shell_exec('command -v '.escapeshellarg($command)) !== '';
    }
}