<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

class AutoConfig implements \ArrayAccess
{

    public string $modelType;

    protected bool $isEncoderDecoder;

    protected array $architectures = [];

    protected int $padTokenId;

    protected int $vocabSize;

    protected int $hiddenSize;

    private function __construct(protected array $config)
    {
        $this->modelType = $this->config['model_type'] ?? null;
        $this->isEncoderDecoder = $this->config['is_encoder_decoder'] ?? false;
        $this->architectures = $this->config['architectures'] ?? [];
        $this->padTokenId = $this->config['pad_token_id'] ?? 0;
        $this->vocabSize = $this->config['vocab_size'] ?? 0;
        $this->hiddenSize = $this->config['hidden_size'] ?? 0;
    }

    public static function fromPretrained(
        string  $modelNameOrPath,
        ?array  $config = null,
        ?string $cacheDir = null,
        string  $revision = 'main',
    ): self
    {
        $data = $config ?? Hub::getJson(
            $modelNameOrPath,
            fileName: 'config.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false
        );

        return new self($data);
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
        throw new \BadMethodCallException("Config is read-only");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException("Config is read-only");
    }
}