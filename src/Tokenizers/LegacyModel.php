<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

use Codewithkyrian\Transformers\Tokenizers\TokenizerModel;

/**
 * Legacy tokenizer class for tokenizers with only a vocabulary.
 */
class LegacyModel extends TokenizerModel
{

    protected ?string $bosToken;
    protected ?int $bosTokenId;

    protected ?string $eosToken;
    protected ?int $eosTokenId;

    protected ?string $padToken;
    protected ?int $padTokenId;

    public function __construct(array $config, ...$moreConfig)
    {
        parent::__construct($config);

//        $vocab = $moreConfig['vocab'] ?? $this->config['vocab'];
//        $this->tokenToIds = self::toMap(
//                $moreConfig['target_lang'] ?? false
//                ? $vocab[$moreConfig['target_lang']]
//                : $vocab
//        );

        $vocab = $moreConfig['target_lang'] ?? false
            ? $config['vocab'][$moreConfig['target_lang']]
            : $config['vocab'];

        foreach ($vocab as $id => [$token, $logProb]) {
            $this->tokenToIds[$token] = $id;
        }

        $this->bosToken = $moreConfig['bos_token'] ?? null;
        $this->bosTokenId = $this->tokenToIds[$this->bosToken] ?? null;

        $this->eosToken = $moreConfig['eos_token'] ?? null;
        $this->eosTokenId = $this->tokenToIds[$this->eosToken] ?? null;

        $this->padToken = $moreConfig['pad_token'] ?? null;
        $this->padTokenId = $this->tokenToIds[$this->padToken] ?? null;

        $this->unkToken = $moreConfig['unk_token'] ?? null;
        $this->unkTokenId = $this->tokenToIds[$this->unkToken] ?? null;


        foreach ($this->tokenToIds as $token => $id) {
            $this->vocab[$id] = $token;
        }
    }

    protected function encode(array $tokens): array
    {
        return $tokens;
    }
}
