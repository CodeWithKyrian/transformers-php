<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Decoders;

class VitsDecoder extends Decoder
{

    protected function decodeChain(array $tokens): array
    {
        $decoded = '';

        for ($i = 1; $i < count($tokens); $i += 2) {
            $decoded .= $tokens[$i];
        }

        return [$decoded];
    }
}
