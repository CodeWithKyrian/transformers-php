<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\FFI\Resolvers;

class WindowsResolver extends Resolver
{

    protected string $extension = 'dll';

    protected array $libDirectories = [
        ['directory' => 'C:\Windows\System32', 'priority' => 0],
    ];


    public function inferInstaller(): ?string
    {
        return 'cli';
    }


    protected function commandExists(string $command): bool
    {
        $output = null;
        $resultCode = null;
        exec("where $command", $output, $resultCode);

        return $resultCode === 0;
    }
}