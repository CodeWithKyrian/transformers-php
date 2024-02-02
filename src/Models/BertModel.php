<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

class BertModel extends PretrainedModel
{
    protected static ModelType $modelType = ModelType::EncoderOnly;
}