<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class BlenderbotTokenizer extends PretrainedTokenizer
{
    protected string $defaultChatTemplate = "{% for message in messages %}{% if message['role'] == 'user' %}{{ ' ' }}{% endif %}{{ message['content'] }}{% if not loop.last %}{{ '  ' }}{% endif %}{% endfor %}{{ eos_token }}";
}
