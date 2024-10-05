<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

use Codewithkyrian\Transformers\PreTokenizers\MetaspacePreTokenizer;

class LlamaTokenizer extends PreTrainedTokenizer
{
    const SPIECE_UNDERLINE = "▁";

    protected string $defaultChatTemplate = "{% if messages[0]['role'] == 'system' %}{% set loop_messages = messages[1:] %}{% set system_message = messages[0]['content'] %}{% elif USE_DEFAULT_PROMPT == true and not '<<SYS>>' in messages[0]['content'] %}{% set loop_messages = messages %}{% set system_message = 'DEFAULT_SYSTEM_MESSAGE' %}{% else %}{% set loop_messages = messages %}{% set system_message = false %}{% endif %}{% for message in loop_messages %}{% if (message['role'] == 'user') != (loop.index0 % 2 == 0) %}{{ raise_exception('Conversation roles must alternate user/assistant/user/assistant/...') }}{% endif %}{% if loop.index0 == 0 and system_message != false %}{% set content = '<<SYS>>\n' + system_message + '\n<</SYS>>\n\n' + message['content'] %}{% else %}{% set content = message['content'] %}{% endif %}{% if message['role'] == 'user' %}{{ bos_token + '[INST] ' + content.strip() + ' [/INST]' }}{% elif message['role'] == 'system' %}{{ '<<SYS>>\n' + content.strip() + '\n<</SYS>>\n\n' }}{% elif message['role'] == 'assistant' %}{{ ' '  + content.strip() + ' ' + eos_token }}{% endif %}{% endfor %}";

    public const DEFAULT_SYSTEM_PROMPT =
        "You are a helpful, respectful and honest assistant. Always answer as helpfully as possible, while being safe. Your " .
        "answers should not include any harmful, unethical, racist, sexist, toxic, dangerous, or illegal content. Please ensure " .
        "that your responses are socially unbiased and positive in nature.\n\n" .
        "If a question does not make any sense, or is not factually coherent, explain why instead of answering something not " .
        "correct. If you don't know the answer to a question, please don't share false information.";

    protected bool $useDefaultSystemPrompt;

    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);

        $this->useDefaultSystemPrompt = $tokenizerConfig['use_default_system_prompt'] ?? false;
        $this->legacy = $tokenizerConfig['legacy'] ?? true;

        if (!$this->legacy) {
            // See https://github.com/huggingface/transformers/pull/24565 for more information
            $this->normalizer = null;
            $this->preTokenizer = new MetaspacePreTokenizer([
                'replacement' => self::SPIECE_UNDERLINE,
                'add_prefix_space' => true,
                'prepend_scheme' => 'first',
            ]);
        }

    }

    /**
     * Helper function to handle legacy encoding of SPM tokenizers.
     *  Adapted from https://github.com/huggingface/transformers/blob/e6dcf8abd6f65bb4b6dfc1831b20d9ba49ce00e2/src/transformers/models/t5/tokenization_t5.py#L374-L387
     *
     * @param ?string $text
     * @param string|null $textPair
     * @param bool $addSpecialTokens
     * @return array
     */
    public function encodeText(?string $text, string $textPair = null, bool $addSpecialTokens = true): array
    {
        if ($text === null) {
            return [];
        }

        if ($this->legacy || strlen($text) == 0) {
            return parent::encodeText($text, $textPair, $addSpecialTokens);
        }

        $tokens = parent::encodeText(self::SPIECE_UNDERLINE . str_replace(self::SPIECE_UNDERLINE, ' ', $text));

        if (count($tokens) > 1 && $tokens[0] === '_' && in_array($tokens[1], $this->specialTokens)) {
            $tokens = array_slice($tokens, 1);
        }

        return $tokens;
    }

    protected function getDefaultChatTemplate(): string
    {
        $defaultChatTemplate = parent::getDefaultChatTemplate();
        $useDefaultPrompt = $this->useDefaultSystemPrompt ? 'true' : 'false';
        $defaultSystemPrompt = str_replace(["\n", "'"], ["\\n", "\\'"], self::DEFAULT_SYSTEM_PROMPT);

        return str_replace(['USE_DEFAULT_PROMPT', 'DEFAULT_SYSTEM_MESSAGE'], [$useDefaultPrompt, $defaultSystemPrompt], $defaultChatTemplate);
    }
}
