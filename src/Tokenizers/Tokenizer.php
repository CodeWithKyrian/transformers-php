<?php /** @noinspection PhpUnreachableStatementInspection */

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

use Codewithkyrian\Transformers\Utils\Hub;

abstract class Tokenizer
{
    /**
     * An array of tokens.
     * @var string[]
     */
    public array $vocab = [];

    /**
     * A mapping of tokens to ids.
     * @var array<string, int>
     */
    public array $tokenToIds = [];

    /**
     *The id of the unknown token.
     */
    protected ?int $unkTokenId = null;

    /**
     * The unknown token string.
     */
    protected ?string $unkToken = null;

    public ?string $endOfWordSuffix = null;

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

    public function __construct(protected array $config)
    {
    }

    /**
     * Instantiates a new TokenizerModel instance based on the configuration object provided.
     */
    public static function fromConfig(array $config, ...$args): self
    {
        return match ($config['type'] ?? null) {
            'WordPiece' => new WordpieceTokenizer($config),
            'Unigram' => new UnigramTokenizer($config, ...$args),
            default => (function () use ($config, $args) {
                if ($config['vocab'] ?? false) {
                    return new LegacyTokenizer($config, ...$args);
                }

                throw new \Exception("Unknown tokenizer type {$config['type']}");
            })()
        };
    }


    /**
     * Internal function to call the TokenizerModel instance.
     * @param string[] $tokens The tokens to encode.
     * @return string[] The encoded token IDs.
     */
    public function __invoke(array $tokens): array
    {
        $ids = $this->encode($tokens);

        if ($this->fuseUnk()) {
            $ids = $this->fuse($ids, $this->unkTokenId, $this->tokenToIds);
        }

        return $ids;
    }

    /**
     * Loads a tokenizer from the specified path.
     *
     * @param string $modelNameOrPath The path to the tokenizer model directory
     * @param bool $quantized
     * @param array|null $config
     * @param string|null $cacheDir
     * @param string $revision
     * @param mixed $legacy
     * @return array {tokenizerJson: array, tokenizerConfig: array}
     */
    public static function load(
        string  $modelNameOrPath,

        bool    $quantized,
        ?array  $config,
        ?string $cacheDir,
        string  $revision,
        mixed   $legacy
    ): array
    {
        $tokenizerJson = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false
        );

        $tokenizerConfig = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer_config.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false
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
     * Encodes a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens The tokens to encode.
     * @return string[] The encoded token IDs.
     */
    protected abstract function encode(array $tokens): array;

    /**
     * Converts a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens The tokens to convert.
     * @return string[] The converted token IDs.
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

    protected function fuseUnk(): bool
    {
        return $this->config['fuse_unk'] ?? false;
    }

    /**
     * Helper function to fuse consecutive values in an array equal to the specified value.
     *
     * @param string[] $arr The input array
     * @param mixed $value The value to fuse on.
     * @param array<string, mixed> $mappings The mapping from input domain to value.
     * @return array
     */
    private static function fuse(array $arr, mixed $value, array $mappings): array
    {
        $fused = [];
        $fusedIds = [];
        $fusedLength = 0;

        foreach ($arr as $i => $v) {
            if ($v === $value) {
                $fusedLength++;
            } else {
                if ($fusedLength > 0) {
                    $fused[] = $mappings[$value];
                    $fusedIds[] = $value;
                    $fusedLength = 0;
                }

                $fused[] = $v;
                $fusedIds[] = $i;
            }
        }

        if ($fusedLength > 0) {
            $fused[] = $mappings[$value];
            $fusedIds[] = $value;
        }

        return $fused;
    }

    /**
     * Helper function to split a string on whitespace.
     *
     * @param string $text The text to split.
     * @return string[] The split text.
     */
    public static function whitespaceSplit(string $text): array
    {
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Helper function to remove accents from a string.
     * @param string $text The text to remove accents from.
     * @return string The text with accents removed.
     */
    public static function removeAccents(string $text): string
    {
        return preg_replace('/[\x{0300}-\x{036f}]/u', '', $text);
    }

    /**
     * Helper function to lowercase a string and remove accents.
     * @param string $text The text to lowercase and remove accents from.
     * @return string The text with accents removed and lowercased.
     */
    public static function lowerCaseAndRemoveAccents(string $text): string
    {
        return mb_strtolower(self::removeAccents($text));
    }

    /**
     * Clean up a list of simple English tokenization artifacts like spaces before punctuations and abbreviated forms
     *
     * @param string $text The text to clean up.
     * @return string The cleaned up text.
     */
    public static function cleanUpTokenization(string|int $text): string
    {
        if (is_int($text)) {
           $text = (string) $text;
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

    public static function toMap(array $arr): array
    {
        $arrayObject = new \ArrayObject($arr);

        return $arrayObject->getArrayCopy();
    }
}