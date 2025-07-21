<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tokenizers;

/**
 * A class representing a node in a BPE tokenizer.
 */
class BPENode
{
    public float $score = 0.0;

    public bool $deleted = false;

    public function __construct(
        public string $token,
        public float  $bias,
        public ?BPENode $prev = null,
        public ?BPENode $next = null,
    ) {}
}
