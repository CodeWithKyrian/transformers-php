<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Configs;

use ArrayAccess;
use Codewithkyrian\Transformers\Utils\Hub;
use function Codewithkyrian\Transformers\Utils\array_pick;
use Codewithkyrian\Transformers\Transformers;

/**
 * The base class that implements the common methods for loading a configuration either from a local file or directory,
 * or from a pretrained model configuration on the Hub.
 *
 * Common attributes present in all config classes are: hidden_size, num_attention_heads, and num_hidden_layers.
 * Text models further implement: vocab_size.
 */
class PretrainedConfig implements ArrayAccess
{
    public ?string $modelType = null;

    public bool $isEncoderDecoder;

    public int $maxPositionEmbeddings;

    public array $normalizedConfig;

    private function __construct(public array $config)
    {
        $this->modelType = $config['model_type'] ?? null;
        $this->isEncoderDecoder = $config['is_encoder_decoder'] ?? false;
        $this->maxPositionEmbeddings = $config['max_position_embeddings'] ?? 0;

        $this->normalizedConfig = $this->getNormalizedConfig($config);
    }

    public static function fromPretrained(
        string    $modelNameOrPath,
        ?array    $config = null,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        ?callable $onProgress = null
    ): self {
        $config ??= Hub::getJson(
            $modelNameOrPath,
            fileName: 'config.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false,
            onProgress: $onProgress
        );
        if ($config === null) {
            $logger = Transformers::getLogger();
            $logger->warning('Config loading returned null, using fallback/defaults');
        }
        return new self($config);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->config[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->config[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->config[$offset]);
    }

    protected function getNormalizedConfig(array $config): array
    {
        $mapping = [];
        $normalizedConfig = [];

        switch ($config['model_type']) {
            // Sub-configs
            case 'llava':
            case 'paligemma':
            case 'gemma3':
            case 'florence2':
            case 'llava_onevision':
            case 'idefics3':
            case 'ultravox':
            case 'smolvlm':
            case 'gemma3n':
                $normalizedConfig = $this->getNormalizedConfig($config['text_config']);
                break;
            case 'moondream1':
                $normalizedConfig = $this->getNormalizedConfig($config['phi_config']);
                break;
            case 'musicgen':
                $normalizedConfig = $this->getNormalizedConfig($config['decoder']);
                break;

            // Decoder-only models
            case 'gpt2':
            case 'gptj':
            case 'jais':
            case 'codegen':
            case 'gpt_bigcode':
                $mapping = [
                    'num_heads' => 'n_head',
                    'num_layers' => 'n_layer',
                    'hidden_size' => 'n_embd',
                ];
                break;
            case 'gpt_neox':
            case 'stablelm':
            case 'opt':
            case 'falcon':
                $mapping = [
                    'num_heads' => 'num_attention_heads',
                    'num_layers' => 'num_hidden_layers',
                    'hidden_size' => 'hidden_size',
                ];
                break;
            case 'llama':
            case 'lfm2':
            case 'smollm3':
            case 'olmo':
            case 'olmo2':
            case 'mobilellm':
            case 'granite':
            case 'cohere':
            case 'mistral':
            case 'starcoder2':
            case 'qwen2':
            case 'qwen2_vl':
            case 'phi':
            case 'phi3':
            case 'phi3_v':
            case 'llava_qwen2':
                $mapping = [
                    'num_heads' => 'num_key_value_heads',
                    'num_layers' => 'num_hidden_layers',
                    'hidden_size' => 'hidden_size',
                    'num_attention_heads' => 'num_attention_heads',
                ];
                break;
            case 'qwen3':
            case 'gemma':
            case 'gemma2':
            case 'gemma3_text':
            case 'gemma3n_text':
            case 'glm':
            case 'helium':
            case 'ernie4_5':
                $mapping = [
                    'num_heads' => 'num_key_value_heads',
                    'num_layers' => 'num_hidden_layers',
                    'dim_kv' => 'head_dim',
                ];
                break;
            case 'openelm':
                $mapping = [
                    'num_heads' => 'num_kv_heads',
                    'num_layers' => 'num_transformer_layers',
                    'dim_kv' => 'head_dim',
                ];
                break;
            case 'gpt_neo':
            case 'donut-swin':
                $mapping = [
                    'num_heads' => 'num_heads',
                    'num_layers' => 'num_layers',
                    'hidden_size' => 'hidden_size',
                ];
                break;
            case 'bloom':
                $mapping = [
                    'num_heads' => 'n_head',
                    'num_layers' => 'n_layer',
                    'hidden_size' => 'hidden_size',
                ];
                break;
            case 'mpt':
                $mapping = [
                    'num_heads' => 'n_heads',
                    'num_layers' => 'n_layers',
                    'hidden_size' => 'd_model',
                ];
                break;

            // Encoder-decoder models
            case 't5':
            case 'mt5':
            case 'longt5':
                $mapping = [
                    'num_decoder_layers' => 'num_decoder_layers',
                    'num_decoder_heads' => 'num_heads',
                    'decoder_dim_kv' => 'd_kv',
                    'num_encoder_layers' => 'num_layers',
                    'num_encoder_heads' => 'num_heads',
                    'encoder_dim_kv' => 'd_kv',
                ];
                break;
            case 'bart':
            case 'mbart':
            case 'marian':
            case 'whisper':
            case 'm2m_100':
            case 'blenderbot':
            case 'blenderbot-small':
            case 'florence2_language':
                $mapping = [
                    'num_decoder_layers' => 'decoder_layers',
                    'num_decoder_heads' => 'decoder_attention_heads',
                    'decoder_hidden_size' => 'd_model',
                    'num_encoder_layers' => 'encoder_layers',
                    'num_encoder_heads' => 'encoder_attention_heads',
                    'encoder_hidden_size' => 'd_model',
                ];
                break;
            case 'speecht5':
                $mapping = [
                    'num_decoder_layers' => 'decoder_layers',
                    'num_decoder_heads' => 'decoder_attention_heads',
                    'decoder_hidden_size' => 'hidden_size',
                    'num_encoder_layers' => 'encoder_layers',
                    'num_encoder_heads' => 'encoder_attention_heads',
                    'encoder_hidden_size' => 'hidden_size',
                ];
                break;
            case 'trocr':
                $mapping = [
                    'num_encoder_layers' => 'decoder_layers',
                    'num_decoder_heads' => 'decoder_attention_heads',
                    'encoder_hidden_size' => 'd_model',
                ];
                break;
            case 'musicgen_decoder':
                $mapping = [
                    'num_encoder_layers' => 'num_hidden_layers',
                    'num_encoder_heads' => 'num_attention_heads',
                    'encoder_hidden_size' => 'hidden_size',
                ];
                break;

            case 'vision-encoder-decoder':
                $decoderConfig = $this->getNormalizedConfig($config['decoder']);
                $addEncoderPkv = array_key_exists('num_decoder_layers', $decoderConfig);
                $result = array_pick($config, ['model_type', 'is_encoder_decoder']);

                if ($addEncoderPkv) {
                    $result = array_merge($result, [
                        'num_decoder_layers' => $decoderConfig['num_decoder_layers'],
                        'num_decoder_heads' => $decoderConfig['num_decoder_heads'],
                        'decoder_hidden_size' => $decoderConfig['decoder_hidden_size'],
                        'num_encoder_layers' => $decoderConfig['num_encoder_layers'],
                        'num_encoder_heads' => $decoderConfig['num_encoder_heads'],
                        'encoder_hidden_size' => $decoderConfig['encoder_hidden_size'],
                    ]);
                } else {
                    $result = array_merge($result, [
                        'num_layers' => $decoderConfig['num_layers'],
                        'num_heads' => $decoderConfig['num_heads'],
                        'hidden_size' => $decoderConfig['hidden_size'],
                    ]);
                }
                return $result;
        }

        // If `num_attention_heads` is not set, assume it's equal to `num_heads`
        $normalizedConfig = array_merge(
            $normalizedConfig,
            array_pick($config, ['model_type', 'multi_query', 'is_encoder_decoder'])
        );

        foreach ($mapping as $key => $value) {
            $normalizedConfig[$key] = $config[$value];
        }

        return $normalizedConfig;
    }


    public function getKeyValueShapes(string $prefix = 'past_key_values'): array
    {
        $decoderFeeds = [];

        // TODO: Support batches (i.e., batchSize > 1)
        $batchSize = 1;

        if (
            ($this->normalizedConfig['is_encoder_decoder'] ?? false) &&
            isset($this->normalizedConfig['num_encoder_heads'], $this->normalizedConfig['num_decoder_heads'])
        ) {
            $encoderDimKv = $this->normalizedConfig['encoder_dim_kv'] ?? (
                $this->normalizedConfig['encoder_hidden_size'] / $this->normalizedConfig['num_encoder_heads']
            );
            $decoderDimKv = $this->normalizedConfig['decoder_dim_kv'] ?? (
                $this->normalizedConfig['decoder_hidden_size'] / $this->normalizedConfig['num_decoder_heads']
            );

            $encoderDims = [$batchSize, $this->normalizedConfig['num_encoder_heads'], 0, $encoderDimKv];
            $decoderDims = [$batchSize, $this->normalizedConfig['num_decoder_heads'], 0, $decoderDimKv];
            for ($i = 0; $i < $this->normalizedConfig['num_decoder_layers']; ++$i) {
                $decoderFeeds["{$prefix}.{$i}.encoder.key"] = $encoderDims;
                $decoderFeeds["{$prefix}.{$i}.encoder.value"] = $encoderDims;
                $decoderFeeds["{$prefix}.{$i}.decoder.key"] = $decoderDims;
                $decoderFeeds["{$prefix}.{$i}.decoder.value"] = $decoderDims;
            }
        } else { // Decoders
            $numHeads = $this->normalizedConfig['num_heads'];
            $numLayers = $this->normalizedConfig['num_layers'];
            $dimKv = $this->normalizedConfig['dim_kv'] ?? (
                $this->normalizedConfig['hidden_size'] /
                ($this->normalizedConfig['num_attention_heads'] ?? $numHeads)
            );

            if ($this->normalizedConfig['model_type'] === 'falcon') {
                $shape = [$batchSize * $numHeads, 0, $dimKv];
                for ($i = 0; $i < $numLayers; ++$i) {
                    $decoderFeeds["{$prefix}.{$i}.key"] = $shape;
                    $decoderFeeds["{$prefix}.{$i}.value"] = $shape;
                }
            } elseif ($this->config['multi_query'] ?? null) { // e.g., for `gpt_bigcode`
                $shape = [$batchSize * $numHeads, 0, 2 * $dimKv];
                for ($i = 0; $i < $numLayers; ++$i) {
                    $decoderFeeds["{$prefix}.{$i}.key_value"] = $shape;
                }
            } elseif ($this->normalizedConfig['model_type'] === 'bloom') {
                $keyDims = [$batchSize * $numHeads, $dimKv, 0];
                $valueDims = [$batchSize * $numHeads, 0, $dimKv];
                for ($i = 0; $i < $numLayers; ++$i) {
                    $decoderFeeds["{$prefix}.{$i}.key"] = $keyDims;
                    $decoderFeeds["{$prefix}.{$i}.value"] = $valueDims;
                }
            } elseif ($this->normalizedConfig['model_type'] === 'openelm') {
                for ($i = 0; $i < $numLayers; ++$i) {
                    $shape = [$batchSize, $numHeads[$i], 0, $dimKv];
                    $decoderFeeds["{$prefix}.{$i}.key"] = $shape;
                    $decoderFeeds["{$prefix}.{$i}.value"] = $shape;
                }
            } else { // Decoder-only
                $shape = [$batchSize, $numHeads, 0, $dimKv];
                for ($i = 0; $i < $numLayers; ++$i) {
                    $decoderFeeds["{$prefix}.{$i}.key"] = $shape;
                    $decoderFeeds["{$prefix}.{$i}.value"] = $shape;
                }
            }
        }

        return $decoderFeeds;
    }
}
