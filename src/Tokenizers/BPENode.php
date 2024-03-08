<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

class BPENode
{
    public float $score = 0.0;

    public bool $deleted = false;

    public function __construct(
        public string $token,
        public float  $bias,
        public ?BPENode $prev = null,
        public ?BPENode $next = null,
    )
    {
    }
}