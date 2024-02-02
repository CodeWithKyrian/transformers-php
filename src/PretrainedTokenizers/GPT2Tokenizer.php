<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

class GPT2Tokenizer extends PretrainedTokenizer
{
     protected string $defaultChatTemplate =  '{% for message in messages %}" "{{ message.content }}{{ eos_token }}" "{% endfor %}';
}