<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

class DistilBertModel extends PretrainedModel
{
    protected static ModelType $modelType = ModelType::EncoderOnly;
}