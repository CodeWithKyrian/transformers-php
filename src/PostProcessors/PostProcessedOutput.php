<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PostProcessors;

class PostProcessedOutput
{
    /**
     * @param string[] $tokens The tokens to be post-processed.
     * @param int[] $tokenTypeIds List of token type ids produced by the post-processor.
     */
    public function __construct(
        public array $tokens,
        public ?array $tokenTypeIds = null,
    )
    {
    }
}