<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

use Codewithkyrian\Transformers\Tokenizers\Tokenizer;

/**
 * Class representing a Unigram tokenizer model.
 */
class UnigramTokenizer extends Tokenizer
{
    protected array $scores = [];

    protected string $bosToken;
    protected int $bosTokenId;

    protected string $eosToken;
    protected int $eosTokenId;

    protected float $minScore = 0;

    protected float $unkScore = 0;

    public function __construct(array $config, ...$moreConfig)
    {
        parent::__construct($config);

        foreach ($config['vocab'] as $piece) {
            $this->vocab[] = $piece[0];
            $this->scores[] = $piece[1];
        }

        $this->unkTokenId = $config['unk_id'];
        $this->unkToken = $this->vocab[$this->unkTokenId];
        $this->tokenToIds = self::toMap(array_map(fn($x, $y) => [$x, $y], $this->vocab, array_keys($this->vocab)));

        $this->bosToken = ' '; // beginning of a sentence token
        $this->bosTokenId = $this->tokenToIds[$this->bosToken] ?? null;

        $this->eosToken = $moreConfig['eos_token'] ?? '</s>'; // end of a sentence token
        $this->eosTokenId = $this->tokenToIds[$this->eosToken] ?? null;

        $this->minScore = min($this->scores)[0];
        $this->unkScore = $this->minScore - 10.0;

    }

    protected function encode(array $tokens): array
    {
        // TODO: Implement encode() method.
    }
}