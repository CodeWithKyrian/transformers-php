<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

class GPT2Tokenizer extends PreTrainedTokenizer
{
     protected string $defaultChatTemplate =  '{% for message in messages %}" "{{ message.content }}{{ eos_token }}" "{% endfor %}';
}
