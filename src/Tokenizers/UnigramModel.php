<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tokenizers;

use Codewithkyrian\Transformers\DataStructures\CharTrie;
use Codewithkyrian\Transformers\DataStructures\TokenLattice;
use function Codewithkyrian\Transformers\Utils\array_pop_key;

/**
 * Class representing a Unigram tokenizer model.
 */
class UnigramModel extends TokenizerModel
{
    protected array $scores = [];

    protected ?string $bosToken;
    protected ?int $bosTokenId;

    protected ?string $eosToken;
    protected ?int $eosTokenId;

    protected float $minScore = 0;

    protected float $unkScore = 0;

    protected CharTrie $trie;


    public function __construct(array $config, ...$args)
    {
        parent::__construct($config);
        $moreConfig = array_pop_key($args, 'tokenizerConfig', []);

        foreach ($config['vocab'] as $piece) {
            $this->vocab[] = $piece[0];
            $this->scores[] = $piece[1];
        }

        $this->unkTokenId = $config['unk_id'];
        $this->unkToken = $this->vocab[$this->unkTokenId];
        $this->tokenToIds = array_flip($this->vocab);

        $this->bosToken = ' '; // beginning of a sentence token
        $this->bosTokenId = $this->tokenToIds[$this->bosToken] ?? null;

        $this->eosToken = $moreConfig['eos_token'] ?? '</s>'; // end of a sentence token
        $this->eosTokenId = $this->tokenToIds[$this->eosToken] ?? null;

        $this->minScore = min($this->scores);
        $this->unkScore = $this->minScore - 10.0;

        $this->scores[$this->unkTokenId] = $this->unkScore;
        $this->trie = new CharTrie();
        $this->trie->extend($this->vocab);

        // NOTE: `fuse_unk` is hardcoded to true for Unigram models
        // See: https://github.com/huggingface/tokenizers/blob/b58227c7f1ccf8b73ee2268354336da56d91e492/tokenizers/src/models/unigram/model.rs#L119
        $this->fuseUnk = true;
    }

    /**
     * Populates lattice nodes.
     * @param TokenLattice $lattice The token lattice to populate with nodes.
     */
    public function populateNodes(TokenLattice $lattice): void
    {
        $sentence = $lattice->sentence;
        $len = mb_strlen($sentence);

        $beginPos = 0;

        while ($beginPos < $len) {
            $mblen = 1;
            $hasSingleNode = false;

            foreach ($this->trie->commonPrefixSearch(mb_substr($sentence, $beginPos)) as $token) {
                $tokenId = $this->tokenToIds[$token];
                $tokenScore = $this->scores[$tokenId];
                $n = mb_strlen($token);
                $lattice->insert($beginPos, $n, $tokenScore, $tokenId);
                if (!$hasSingleNode && $n === $mblen) {
                    $hasSingleNode = true;
                }
            }
            if (!$hasSingleNode) {
                $lattice->insert($beginPos, $mblen, $this->unkScore, $this->unkTokenId);
            }
            $beginPos += $mblen;
        }
    }

    /**
     * Encodes an array of tokens into an array of subtokens using the unigram model.
     *
     * @param string $normalized The normalized string.
     * @return string[] An array of subtokens obtained by encoding the input tokens using the unigram model.
     */
    public function tokenize(string $normalized): array
    {
        $lattice = new TokenLattice($normalized, $this->bosTokenId, $this->eosTokenId);
        $this->populateNodes($lattice);
        return $lattice->tokens();
    }

    /**
     * Encodes an array of tokens using Unigram encoding.
     * @param string[] $tokens The tokens to encode.
     * @return string[] An array of encoded tokens.
     */
    protected function encode(array $tokens): array
    {
        $toReturn = [];
        foreach ($tokens as $token) {
            $tokenized = $this->tokenize($token);
            $toReturn = array_merge($toReturn, $tokenized);
        }
        return $toReturn;
    }
}
