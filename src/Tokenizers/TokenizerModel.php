<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tokenizers;

use Codewithkyrian\Transformers\Exceptions\HubException;
use Codewithkyrian\Transformers\Utils\Hub;
use Exception;

abstract class TokenizerModel
{
    public const SPECIAL_TOKEN_ATTRIBUTES = [
        'bos_token',
        'eos_token',
        'unk_token',
        'sep_token',
        'pad_token',
        'cls_token',
        'mask_token',
        // additional_special_tokens (TODO)
    ];

    /**
     * An array of tokens.
     *
     * @var string[]
     */
    public array $vocab = [];

    /**
     * A mapping of tokens to ids.
     *
     * @var array<string, int>
     */
    public array $tokenToIds = [];

    /**
     * The suffix of the end of word.
     */
    public ?string $endOfWordSuffix = null;

    /**
     * The prefix of the continuing subword.
     */
    public ?string $continuingSubwordPrefix = null;

    /**
     *The id of the unknown token.
     */
    protected ?int $unkTokenId = null;

    /**
     * The unknown token string.
     */
    protected ?string $unkToken = null;

    /**
     * Whether to fuse the unknown token into the vocabulary.
     */
    protected bool $fuseUnk = false;

    /**
     * Constructs a new TokenizerModel instance.
     *
     * @param array $config The configuration for the tokenizer model.
     */
    public function __construct(protected array $config)
    {
        $this->continuingSubwordPrefix = $config['continuing_subword_prefix'] ?? null;
        if ($this->continuingSubwordPrefix == "") {
            $this->continuingSubwordPrefix = null;
        }

        $this->fuseUnk = $config['fuse_unk'] ?? false;
    }

    /**
     * Instantiates a new TokenizerModel instance based on the configuration object provided.
     */
    public static function fromConfig(array $config, ...$args): self
    {
        $type = $config['type'] ?? null;

        return match ($type) {
            'WordPiece' => new WordPieceModel($config),
            'Unigram' => new UnigramModel($config, ...$args),
            'BPE' => new BPEModel($config),
            default => self::inferTokenizerModel($type, $config, $args),
        };
    }

    /**
     * Infers the tokenizer model based on the pretokenizer configuration.
     *
     * This function is necessary for legacy tokenizer.json files that do not contain the model.type key.
     *
     * @param string $type The tokenizer type.
     * @param array $config The tokenizer configuration.
     * @param array $args Additional arguments that may include pretokenizerConfig.
     * @return TokenizerModel The inferred tokenizer model instance.
     * @throws Exception If the tokenizer type is unknown.
     */
    private static function inferTokenizerModel(?string $type, array $config, array &$args): TokenizerModel
    {
        if (isset($config['vocab'])) {
            if (is_array($config['vocab']) && array_is_list($config['vocab'])) {
                return new UnigramModel($config, ...$args);
            } elseif (array_key_exists('continuing_subword_prefix', $config) && array_key_exists('unk_token', $config)) {
                if (array_key_exists('merges', $config)) {
                    return new BPEModel($config);
                } else {
                    return new WordPieceModel($config);
                }
            } else {
                return new LegacyModel($config, ...$args);
            }
        }

        throw new Exception("Unknown tokenizer type: " . $type ?? 'undefined');
    }

    /**
     * Loads a tokenizer from the specified path.
     *
     * @param string $modelNameOrPath The path to the tokenizer model directory
     * @param string|null $cacheDir
     * @param string $revision
     * @param mixed $legacy
     * @param callable|null $onProgress
     *
     * @return array {tokenizerJson: array, tokenizerConfig: array}
     * @throws HubException
     */
    public static function load(
        string    $modelNameOrPath,
        ?string   $cacheDir,
        string    $revision,
        mixed     $legacy,
        ?callable $onProgress = null
    ): array {
        $tokenizerJson = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false,
            onProgress: $onProgress
        );

        $tokenizerConfig = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer_config.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false,
            onProgress: $onProgress
        );

        if ($legacy != null) {
            $tokenizerConfig['legacy'] = $legacy;
        }

        return [
            'tokenizerJson' => $tokenizerJson,
            'tokenizerConfig' => $tokenizerConfig,
        ];
    }

    /**
     * Clean up a list of simple English tokenization artifacts like spaces before punctuations and abbreviated forms
     *
     * @param string $text The text to clean up.
     *
     * @return string The cleaned up text.
     */
    public static function cleanUpTokenization(string|int $text): string
    {
        if (is_int($text)) {
            $text = (string)$text;
        }

        $text = preg_replace('/ \./', '.', $text);
        $text = preg_replace('/ \?/', '?', $text);
        $text = preg_replace('/ \!/', '!', $text);
        $text = preg_replace('/ ,/', ',', $text);
        $text = preg_replace('/ \' /', "'", $text);
        $text = preg_replace('/ n\'t/', "n't", $text);
        $text = preg_replace('/ \'m/', "'m", $text);
        $text = preg_replace('/ \'s/', "'s", $text);
        $text = preg_replace('/ \'ve/', "'ve", $text);

        return preg_replace('/ \'re/', "'re", $text);
    }

    /**
     * Internal function to call the TokenizerModel instance.
     *
     * @param string[] $tokens The tokens to encode.
     *
     * @return string[] The encoded token IDs.
     */
    public function __invoke(array $tokens): array
    {
        $ids = $this->encode($tokens);

        if ($this->fuseUnk) {
            $ids = $this->fuse($ids, $this->unkTokenId, $this->tokenToIds);
        }

        return $ids;
    }

    /**
     * Encodes a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens The tokens to encode.
     *
     * @return string[] The encoded token IDs.
     */
    protected abstract function encode(array $tokens): array;

    /**
     * Helper function to fuse consecutive values in an array equal to the specified value.
     *
     * @param array $arr The input array.
     * @param mixed $value The value to fuse on.
     * @param array $mapping The mapping from input domain to value.
     *
     * @return array The fused array.
     */
    protected function fuse(array $arr, mixed $value, array $mapping): array
    {
        $fused = [];
        $i = 0;
        $length = count($arr);

        while ($i < $length) {
            $fused[] = $arr[$i];

            // Check if the current element's mapping is not equal to the specified value
            if (($mapping[$arr[$i]] ?? $value) !== $value) {
                $i++;
                continue;
            }

            // Skip consecutive elements equal to the specified value
            while ($i < $length && ($mapping[$arr[$i]] ?? $value) === $value) {
                $i++;
            }
        }

        return $fused;
    }

    /**
     * Converts a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens The tokens to convert.
     *
     * @return int[] The converted token IDs.
     */
    public function convertTokensToIds(array $tokens): array
    {
        $ids = [];

        foreach ($tokens as $token) {
            $ids[] = $this->tokenToIds[$token] ?? $this->unkTokenId;
        }

        return $ids;
    }

    /**
     * Converts a list of token IDs into a list of tokens.
     *
     * @param string[] $ids The token IDs to convert.
     *
     * @return string[] The converted tokens.
     */
    public function convertIdsToTokens(array $ids): array
    {
        $tokens = [];

        foreach ($ids as $id) {
            $tokens[] = $this->vocab[$id] ?? $this->unkToken;
        }

        return $tokens;
    }
}
