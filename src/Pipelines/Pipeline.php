<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\PreTrainedModel;
use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;
use Codewithkyrian\Transformers\Utils\Tensor;

class Pipeline
{
    public function __construct(
        protected string|Task  $task,
        protected PreTrainedModel  $model,
        protected ?PretrainedTokenizer $tokenizer = null,
        protected ?string $processor = null,
    )
    {
    }


    public function __invoke(...$args): array|Tensor
    {
       return [];
    }

}