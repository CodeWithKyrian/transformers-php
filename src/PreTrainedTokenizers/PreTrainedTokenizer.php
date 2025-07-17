<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

use Closure;
use Codewithkyrian\Jinja\Template;
use Codewithkyrian\Transformers\Decoders\Decoder;
use Codewithkyrian\Transformers\Normalizers\Normalizer;
use Codewithkyrian\Transformers\PostProcessors\PostProcessedOutput;
use Codewithkyrian\Transformers\PostProcessors\PostProcessor;
use Codewithkyrian\Transformers\PreTokenizers\PreTokenizer;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Tokenizers\AddedToken;
use Codewithkyrian\Transformers\Tokenizers\TokenizerModel;
use Error;
use Exception;
use function Codewithkyrian\Transformers\Utils\timeUsage;

class PreTrainedTokenizer
{
    public ?TokenizerModel $model;
    public ?string $maskToken = null;
    public ?int $maskTokenId = null;
    public ?int $sepTokenId = null;
    public string $paddingSide;
    protected bool $warnedAboutChatTemplate = false;
    protected string $defaultChatTemplate = "{% for message in messages %}{{'<|im_start|>' + message['role'] + '\\n' + message['content'] + '<|im_end|>' + '\\n'}}{% endfor %}{% if add_generation_prompt %}{{ '<|im_start|>assistant\\n' }}{% endif %}";
    protected ?Normalizer $normalizer;
    protected ?PreTokenizer $preTokenizer;
    protected ?PostProcessor $postProcessor;
    protected ?Decoder $decoder;
    protected array $specialTokens = [];
    protected array $allSpecialIds = [];
    /**
     * @var AddedToken[]
     */
    protected array $addedTokens = [];
    protected array $additionalSpecialTokens = [];
    protected string $addedTokensRegex = '';
    protected ?string $padToken = null;
    protected ?int $padTokenId = null;
    protected ?string $sepToken = null;
    protected ?string $unkToken = null;
    protected ?int $unkTokenId = null;
    protected mixed $modelMaxLength;
    protected bool $removeSpace;
    protected bool $cleanUpTokenizationSpaces;
    protected bool $doLowerCaseAndRemoveAccent;
    protected bool $legacy;

    protected mixed $chatTemplate;
    protected array $compiledTemplateCache = [];

    /**
     * @param array $tokenizerJSON The JSON of the tokenizer.
     * @param ?array $tokenizerConfig The config of the tokenizer.
     *
     * @throws Exception
     */
    public function __construct(protected array $tokenizerJSON, protected ?array $tokenizerConfig)
    {
        // Construct parts of the tokenizer from the JSON
        $this->normalizer = Normalizer::fromConfig($this->tokenizerJSON['normalizer']);
        $this->preTokenizer = PreTokenizer::fromConfig($this->tokenizerJSON['pre_tokenizer']);
        $this->model = TokenizerModel::fromConfig(
            config: $this->tokenizerJSON['model'],
            tokenizerConfig: $this->tokenizerConfig,
            pretokenizerConfig: $this->tokenizerJSON['pre_tokenizer'],
            decoderConfig: $this->tokenizerJSON['decoder']
        );
        $this->postProcessor = PostProcessor::fromConfig($this->tokenizerJSON['post_processor'] ?? null);
        $this->decoder = Decoder::fromConfig($this->tokenizerJSON['decoder']);


        foreach ($this->tokenizerJSON['added_tokens'] as $addedToken) {
            $token = AddedToken::make($addedToken);
            $this->addedTokens[] = $token;

            $this->model->tokenToIds[$token->content] = $token->id;
            $this->model->vocab[$token->id] = $token->content;

            if ($token->special) {
                $this->specialTokens[] = $token->content;
                $this->allSpecialIds[] = $token->id;
            }
        }

        // Update additional_special_tokens
        $this->additionalSpecialTokens = $this->tokenizerConfig['additional_special_tokens'] ?? [];
        $this->specialTokens = [...$this->specialTokens, ...$this->additionalSpecialTokens];
        $this->specialTokens = array_unique($this->specialTokens);

        if ($this->decoder != null) {
            // Slight hack, but it prevents code duplication:
            $this->decoder->addedTokens = $this->addedTokens;
            $this->decoder->endOfWordSuffix = $this->model->endOfWordSuffix;
        }

        if (count($this->addedTokens) > 0) {
            $addedTokensPatterns = array_map(function ($x) {
                $lstrip = $x->lStrip ? '\s*' : '';
                $rstrip = $x->rStrip ? '\s*' : '';
                return $lstrip . '(' . preg_quote($x->content, '/') . ')' . $rstrip;
            }, $this->addedTokens);

            $this->addedTokensRegex = '/' . implode('|', $addedTokensPatterns) . '/';
        }

        // Set mask token if present
        $this->maskToken = $this->getToken('mask_token');
        $this->maskTokenId = $this->model->tokenToIds[$this->maskToken] ?? null;

        $this->padToken = $this->getToken('pad_token', 'eos_token');
        $this->padTokenId = $this->model->tokenToIds[$this->padToken] ?? null;

        $this->sepToken = $this->getToken('sep_token');
        $this->sepTokenId = $this->model->tokenToIds[$this->sepToken] ?? null;

        $this->unkToken = $this->getToken('unk_token');
        $this->unkTokenId = $this->model->tokenToIds[$this->unkToken] ?? null;

        $this->modelMaxLength = $tokenizerConfig['model_max_length'] ?? null;

        // Whether to strip the text when tokenizing (removing excess spaces before and after the string).
        $this->removeSpace = $tokenizerConfig['remove_space'] ?? false;

        $this->cleanUpTokenizationSpaces = $tokenizerConfig['clean_up_tokenization_spaces'] ?? true;
        $this->doLowerCaseAndRemoveAccent = $tokenizerConfig['do_lowercase_and_remove_accent'] ?? false;

        // Padding side
        $this->paddingSide = 'right'; // Default value, change as needed

        $this->legacy = false;

        $this->chatTemplate = $tokenizerConfig['chat_template'] ?? null;
    }

    /**
     * Returns the value of the first matching key in the tokenizer config array.
     *
     * @param string ...$keys One or more keys to search for in the tokenizer config array.
     *
     * @return string|null The value associated with the first matching key, or null if no match is found.
     *
     * @throws Exception If an object is found for a matching key and its __type property is not "AddedToken".
     */
    protected function getToken(string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $item = $this->tokenizerConfig[$key] ?? null;

            if ($item === null) {
                continue;
            }

            if (is_array($item)) {
                if ($item['__type'] == 'AddedToken') {
                    return $item['content'];
                } else {
                    throw new Exception("Unknown token: " . json_encode($item));
                }
            } else {
                return $item;
            }
        }

        return null;
    }

    /**
     * Loads a pre-trained tokenizer from the given path or name.
     *
     * @param string $modelNameOrPath
     * @param string|null $cacheDir
     * @param string $revision
     * @param null $legacy
     *
     * @return PreTrainedTokenizer
     */
    public static function fromPretrained(
        string  $modelNameOrPath,
        ?string $cacheDir = null,
        string  $revision = 'main',
        $legacy = null,
    ): PreTrainedTokenizer {
        ['tokenizerJson' => $tokenizerJson, 'tokenizerConfig' => $tokenizerConfig] =
            TokenizerModel::load($modelNameOrPath, $cacheDir, $revision, $legacy);

        return new PreTrainedTokenizer($tokenizerJson, $tokenizerConfig);
    }

    /**
     * Tokenize the given text(s).
     *
     * @param string|array $text The text to tokenize.
     * @param string|array|null $textPair Optional second sequence to be encoded. If set, must be the same type as text.
     * @param bool|string $padding Whether to pad the input sequences.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     * @param bool $truncation Whether to truncate the input sequences.
     * @param int|null $maxLength Maximum length of the returned list and optionally padding length.
     *
     * @return array{input_ids: Tensor, attention_mask: Tensor, token_type_ids: Tensor|null}
     */
    public function tokenize(
        string|array      $text,
        string|array|null $textPair = null,
        bool|string       $padding = false,
        bool              $addSpecialTokens = true,
        bool              $truncation = false,
        ?int              $maxLength = null,
        bool              $returnTensor = true
    ): array {
        return $this->__invoke($text, $textPair, $padding, $addSpecialTokens, $truncation, $maxLength, $returnTensor);
    }

    /**
     * Encode/tokenize the given text(s).
     *
     * @param string|array $text The text to tokenize.
     * @param string|array|null $textPair Optional second sequence to be encoded. If set, must be the same type as text.
     * @param bool|string $padding Whether to pad the input sequences.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     * @param bool $truncation Whether to truncate the input sequences.
     * @param int|null $maxLength Maximum length of the returned list and optionally padding length.
     * @param bool $returnTensor Whether to return the result as a Tensor. If false, the result will be an array.
     *
     * @return array{input_ids: Tensor|array, attention_mask: Tensor|array, token_type_ids: Tensor|array|null}
     */
    public function __invoke(
        string|array      $text,
        string|array|null $textPair = null,
        bool|string       $padding = false,
        bool              $addSpecialTokens = true,
        bool              $truncation = false,
        ?int              $maxLength = null,
        bool              $returnTensor = true
    ): array {
        $isBatched = is_array($text);

        $encodedTokens = [];

        if ($isBatched) {
            if (count($text) === 0) {
                throw new Exception('$text array must be non-empty');
            }

            if ($textPair !== null) {
                if (!is_array($textPair)) {
                    throw new Exception('$textPair must also be an array');
                } elseif (count($text) !== count($textPair)) {
                    throw new Exception('$text and $textPair must have the same length');
                }

                $encodedTokens = array_map(
                    fn($t, $i) => $this->encodePlus($t, $textPair[$i], $addSpecialTokens),
                    $text,
                    array_keys($text)
                );
            } else {
                $encodedTokens = array_map(
                    fn($x) => $this->encodePlus($x, addSpecialTokens: $addSpecialTokens),
                    $text
                );
            }
        } else {
            if (is_array($textPair)) {
                throw new Exception('When specifying `$textPair`, since `$text` is a string, `$textPair` must also be a string (i.e., not an array).');
            }

            // For single input, we just wrap in an array, and then unwrap later.
            $encodedTokens = [$this->encodePlus($text, $textPair, $addSpecialTokens)];
        }

        // At this point, tokens is batched: [batch_size, tokens]
        // However, array may be jagged. So, we pad to max_length

        if ($maxLength === null) {
            if ($padding === 'max_length') {
                $maxLength = $this->modelMaxLength;
            } else {
                // Calculate max length from sequences
                $maxLength = max(array_map(fn($x) => count($x['input_ids']), $encodedTokens));
            }
        } else {
            if (!$truncation) {
                trigger_error("Truncation was not explicitly activated but `maxLength` is provided a specific value, please use `truncation=true` to explicitly truncate examples to max length.", E_USER_WARNING);
            }
        }

        // Ensure it is less than model max length
        $maxLength = min($maxLength, $this->modelMaxLength);


        if ($padding || $truncation) {
            for ($i = 0; $i < count($encodedTokens); $i++) {
                $token = &$encodedTokens[$i];
                if (count($token['input_ids']) === $maxLength) {
                    continue;
                } elseif (count($token['input_ids']) > $maxLength) {
                    // possibly truncate
                    if ($truncation) {
                        $this->truncateHelper($token, $maxLength);
                    }
                } else {
                    // t.length < max_length
                    // possibly pad
                    if ($padding) {
                        $this->padHelper(
                            $token,
                            $maxLength,
                            fn($key) => $key === 'input_ids' ? $this->padTokenId : 0,
                            $this->paddingSide
                        );
                    }
                }
                // Update the encodedTokens array with the modified token
                //    $encodedTokens[$i] = $token;
            }
        }

        if ($returnTensor) {
            if (!($padding && $truncation)) {
                // Not guaranteed that all items have the same length, so we perform additional check
                if (
                    array_reduce($encodedTokens, function ($carry, $x) use ($encodedTokens) {
                        foreach ($x as $key => $value) {
                            if (count($value ?? []) !== count($encodedTokens[0][$key] ?? [])) {
                                return true;
                            }
                        }
                        return $carry;
                    }, false)
                ) {
                    throw new Error("Unable to create tensor, you should probably activate truncation and/or padding with 'padding=true' and 'truncation=true' to have batched tensors with the same length.");
                }
            }

            // Now we actually convert to Tensor
            // NOTE: In the same way as the python library, we return a batched tensor, regardless of whether
            // we have a single input or multiple inputs.
            $shape = [count($encodedTokens), count($encodedTokens[0]['input_ids'])];
            $result = [];


            foreach ($encodedTokens[0] as $key => $value) {
                if ($value === null) {
                    continue;
                }

                $array = array_map(fn($x) => $x[$key], $encodedTokens);

                $result[$key] = new Tensor($array, Tensor::int64, $shape);
            }
        } else {
            $result = [];

            foreach ($encodedTokens[0] as $key => $value) {
                $result[$key] = array_map(fn($x) => $x[$key], $encodedTokens);
            }

            // If not returning a tensor, we match the input type
            if (!$isBatched) {
                foreach ($result as $key => $value) {
                    $result[$key] = $value[0];
                }
            }
        }

        return $result;
    }

    /**
     * Encodes a single text or a pair of texts using the model's tokenizer.
     *
     * @param string|null $text The first sequence to encode.
     * @param string|null $textPair The second sequence to encode.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     *
     * @return array{input_ids: int[], attention_mask: int[], token_type_ids: int[]|null}
     */
    public function encodePlus(
        string|null $text,
        string|null $textPair = null,
        bool        $addSpecialTokens = true
    ): array {
        // Function called by users to encode possibly multiple texts
        $tokens = $this->encodeText($text);

        $tokens2 = $this->encodeText($textPair);

        $combinedTokens = $this->postProcessor
            ? $this->postProcessor->postProcess($tokens, $tokens2, addSpecialTokens: $addSpecialTokens)
            : new PostProcessedOutput(tokens: array_merge($tokens ?? [], $tokens2 ?? []));

        $inputIds = $this->model->convertTokensToIds($combinedTokens->tokens);

        return [
            "input_ids" => $inputIds,
            "attention_mask" => array_fill(0, count($inputIds), 1),
            "token_type_ids" => $combinedTokens->tokenTypeIds,
        ];
    }

    /**
     * Encodes a single text using the preprocessor pipeline of the tokenizer.
     *
     * @param string|null $text The text to encode.
     *
     * @return string[]|null The encoded tokens.
     */
    protected function encodeText(?string $text): ?array
    {
        if ($text === null) {
            return null;
        }

        // Actual function which does encoding, for a single text
        // First, we take care of special tokens. Needed to avoid issues arising from
        // normalization and/or pretokenization (which may not preserve special tokens)
        $sections = $this->addedTokensRegex ? preg_split($this->addedTokensRegex, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) : [$text];

        $tokens = array_map(function ($x, $sectionIndex) {
            $addedToken = null;
            foreach ($this->addedTokens as $token) {
                if ($token->content === $x) {
                    $addedToken = $token;
                    break;
                }
            }

            if ($addedToken !== null) {
                // Ignore added tokens
                return [$x];
            } else {
                if ($this->removeSpace) {
                    $x = preg_replace('/\s+/', ' ', trim($x));
                }

                if ($this->doLowerCaseAndRemoveAccent) {
                    $x = $this->lowerCaseAndRemoveAccents($x);
                }

                if ($this->normalizer !== null) {
                    $x = $this->normalizer->normalize($x);
                }

                // If, after normalization, this section is empty (e.g., trimming whitespace),
                // we return an empty array
                if (mb_strlen($x) === 0) {
                    return [];
                }

                $sectionTokens = $this->preTokenizer !== null
                    ? $this->preTokenizer->preTokenize($x, ['section_index' => $sectionIndex])
                    : [$x];

                return $this->model->__invoke($sectionTokens);
            }
        }, $sections, array_keys($sections));

        return array_merge(...$tokens);
    }

    /**
     * Helper function for truncating values of an object, which are each arrays.
     * NOTE: No additional checks are made here for the validity of arguments.
     *
     * @param array $item The input object.
     * @param int $length The length to truncate to.
     */
    function truncateHelper(array &$item, int $length): void
    {
        // Setting .length to a lower value truncates the array in-place.
        // Note: In PHP, arrays automatically adjust their size, so we don't need to explicitly set the length.
        foreach (array_keys($item) as $key) {
            if (false == $item[$key]) {
                continue;
            }

            $item[$key] = array_slice($item[$key], 0, $length);
        }
    }

    /**
     * Helper function for padding values of an object, which are each arrays.
     * NOTE: No additional checks are made here for the validity of arguments.
     *
     * @param array $item The input object.
     * @param int $length The length to pad to.
     * @param Closure $value_fn Determine the value to fill the array, based on its key.
     * @param string $side Which side to pad the array.
     */
    protected function padHelper(array &$item, int $length, Closure $value_fn, string $side): void
    {
        foreach (array_keys($item) as $key) {
            if ($item[$key] == null) return;

            $diff = $length - count($item[$key]);
            $value = $value_fn($key);

            $padData = array_fill(0, $diff, $value);
            $item[$key] = ($side === 'right')
                ? [...$item[$key], ...$padData]
                : [...$padData, ...$item[$key]];
        }
    }

    /**
     * Encodes a single text or a pair of texts using the model's tokenizer.
     *
     * @param string $text
     * @param string|null $textPair The optional second text to encode.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     *
     * @return array
     */
    public function encode(string $text, ?string $textPair = null, bool $addSpecialTokens = true): array
    {
        return $this->encodePlus($text, $textPair, $addSpecialTokens)['input_ids'];
    }

    /**
     * Decode a batch of tokenized sequences.
     *
     * @param int[]|int[][] $batch The batch of tokenized sequences to decode.
     * @param bool $skipSpecialTokens If true, special tokens are removed from the output string.
     * @param ?bool $cleanUpTokenizationSpaces If true, spaces before punctuations and abbreviated forms are removed.
     *
     * @return string[]
     */
    public function batchDecode(array|Tensor $batch, bool $skipSpecialTokens = false, ?bool $cleanUpTokenizationSpaces = null): array
    {
        if ($batch instanceof Tensor) $batch = $batch->toArray();
        return array_map(fn($x) => $this->decode($x, $skipSpecialTokens, $cleanUpTokenizationSpaces), $batch);
    }

    /**
     * Decodes a sequence of token IDs back to a string.
     *
     * @param array $tokenIds The token IDs to decode.
     * @param bool $skipSpecialTokens Whether to remove all the special tokens from the output string.
     * @param ?bool $cleanUpTokenizationSpaces If true, spaces before punctuations and abbreviated forms are removed.
     *
     * @return string
     */
    public function decode(array $tokenIds, bool $skipSpecialTokens = false, ?bool $cleanUpTokenizationSpaces = null): string
    {
        if (empty($tokenIds) || !is_int($tokenIds[0])) {
            throw new Exception("token_ids must be a non-empty array of integers.");
        }

        return $this->decodeSingle($tokenIds, $skipSpecialTokens, $cleanUpTokenizationSpaces);
    }

    /**
     * Decode a single list of token ids to a string.
     *
     * @param array $tokenIds The token IDs to decode.
     * @param bool $skipSpecialTokens Whether to remove all the special tokens from the output string.
     * @param bool $cleanUpTokenizationSpaces If true, spaces before punctuations and abbreviated forms are removed.
     *
     * @return string
     */
    private function decodeSingle(array $tokenIds, bool $skipSpecialTokens = false, ?bool $cleanUpTokenizationSpaces = null): string
    {
        $tokens = $this->model->convertIdsToTokens($tokenIds);

        if ($skipSpecialTokens) {
            $tokens = array_values(array_filter($tokens, fn($x) => !in_array($x, $this->specialTokens)));
        }

        // If `this.decoder` is null, we just join tokens with a space:
        // https://github.com/huggingface/tokenizers/blob/8edec536a737cb04494b454805be16c020abb14f/tokenizers/src/tokenizer/mod.rs#L835
        $decoded = $this->decoder
            ? $this->decoder->decode($tokens)
            : implode(' ', $tokens);

        // Slight hack, but prevents having to pass `skip_special_tokens` to
        // each call to `decode`, which would lead to code duplication.
        if ($this->decoder?->endOfWordSuffix) {
            $decoded = str_replace($this->decoder->endOfWordSuffix, ' ', $decoded);
            if ($skipSpecialTokens) {
                $decoded = rtrim($decoded);
            }
        }

        if ($cleanUpTokenizationSpaces ?? $this->cleanUpTokenizationSpaces) {
            $decoded = TokenizerModel::cleanUpTokenization($decoded);
        }

        return $decoded;
    }

    /**
     *  Converts a list of message objects with `"role"` and `"content"` keys to a list of token
     *  ids. This method is intended for use with chat models, and will read the tokenizer's chat_template attribute to
     *  determine the format and control tokens to use when converting. When chat_template is None, it will fall back
     *  to the default_chat_template specified at the class level.
     *
     *  See [here](https://huggingface.co/docs/transformers/chat_templating) for more information.
     *
     *  **Example:** Applying a chat template to a conversation.
     *
     * ```php
     * $tokenizer = AutoTokenizer::fromPretrained('mistralai/Mistral-7B-Instruct-v0.1');
     * $messages = [
     *   ['role' => 'user', 'content' => 'Hello!'],
     *  ['role' => 'assistant', 'content' => 'Hi! How are you?'],
     * ['role' => 'user', 'content' => 'I am doing great.'],
     * ['role' => 'assistant', 'content' => 'That is great to hear.'],
     * ];
     *
     * $text = $tokenizer->applyChatTemplate($messages, tokenize: false);
     * // "<s>[INST] Hello, how are you? [/INST]I'm doing great. How can I help you today?</s> [INST] I'd like to show off how chat templating works! [/INST]"
     *
     * $inputIds = $tokenizer->applyChatTemplate($messages, tokenize: true);
     * // [1, 733, 16289, 28793, 22557, 28725, 910, 460, 368, 28804, 733, 28748, 16289, 28793, 28737, 28742, 28719, 2548, 1598, 28723, 1602, 541, 315, 1316, 368, 3154, 28804, 2, 28705, 733, 16289, 28793, 315, 28742, 28715, 737, 298, 1347, 805, 910, 10706, 5752, 1077, 3791, 28808, 733, 28748, 16289, 28793]
     *
     * @param array<array{ role: string, content : string }> $conversation A list of message objects with "role" and "content" keys.
     * @param ?string $chatTemplate The template to use when converting the conversation to tokens.
     * @param bool $addGenerationPrompt Whether to add the generation prompt to the end of the conversation.
     * @param bool $tokenize Whether to return the token ids or the string.
     * @param bool $padding Whether to pad the returned token ids.
     * @param bool $truncation Whether to truncate the returned token ids.
     * @param ?int $maxLength The maximum length to pad/truncate the returned token ids.
     *
     * @return string|int[]|int[][] The token ids or string, depending on the value of `tokenize`.
     */
    public function applyChatTemplate(
        array   $conversation,
        ?string $chatTemplate = null,
        bool    $addGenerationPrompt = false,
        bool    $tokenize = true,
        bool    $padding = false,
        bool    $truncation = false,
        ?int    $maxLength = null,
        bool    $returnTensor = true
    ): string|array {
        $chatTemplate ??= $this->chatTemplate ?? $this->getDefaultChatTemplate();

        // Compilation function uses a cache to avoid recompiling the same template
        $compiledTemplate = $this->compiledTemplateCache[$chatTemplate] ?? null;

        if ($compiledTemplate === null) {
            $compiledTemplate = new Template($chatTemplate);
            $this->compiledTemplateCache[$chatTemplate] = $compiledTemplate;
        }

        $specialTokensMap = [];
        foreach (TokenizerModel::SPECIAL_TOKEN_ATTRIBUTES as $key) {
            $value = $this->getToken($key);
            if ($value !== null) {
                $specialTokensMap[$key] = $value;
            }
        }


        $rendered = $compiledTemplate->render(array_merge([
            'messages' => $conversation,
            'add_generation_prompt' => $addGenerationPrompt,
        ], $specialTokensMap));

        if ($tokenize) {
            return $this->__invoke(
                text: $rendered,
                padding: $padding,
                addSpecialTokens: false,
                truncation: $truncation,
                maxLength: $maxLength,
                returnTensor: $returnTensor
            )['input_ids'];
        }

        return stripcslashes($rendered);
    }

    protected function getDefaultChatTemplate(): string
    {
        //        if (!$this->warnedAboutChatTemplate) {
        //            trigger_error("The default chat template is deprecated and will be removed in a future version. Please use the `chat_template` option instead.", E_USER_WARNING);
        //            $this->warnedAboutChatTemplate = true;
        //        }

        return $this->defaultChatTemplate;
    }

    /**
     * Helper function to lowercase a string and remove accents.
     *
     * @param string $text The text to lowercase and remove accents from.
     *
     * @return string The text with accents removed and lowercased.
     */
    protected function lowerCaseAndRemoveAccents(string $text): string
    {
        return mb_strtolower($this->removeAccents($text));
    }

    /**
     * Helper function to remove accents from a string.
     *
     * @param string $text The text to remove accents from.
     *
     * @return string The text with accents removed.
     */
    protected function removeAccents(string $text): string
    {
        return preg_replace('/[\x{0300}-\x{036f}]/u', '', $text);
    }
}
