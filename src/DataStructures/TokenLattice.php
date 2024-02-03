<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\DataStructures;

/**
 * A lattice data structure to be used for tokenization.
 */
class TokenLattice
{

    /** @var int The length of the input sentence. */
    public int $len;


    /** @var TokenLatticeNode[] An array of nodes representing the lattice nodes. */
    public array $nodes = [];

    /** @var array An array of nodes representing the beginning nodes in the lattice. */
    public array $beginNodes = [];

    /** @var array An array of nodes representing the ending nodes in the lattice. */
    public array $endNodes = [];

    /**
     * Creates a new TokenLattice instance.
     *
     * @param string $sentence The input sentence to be tokenized.
     * @param int $bosTokenId The beginning-of-sequence token ID.
     * @param int $eosTokenId The end-of-sequence token ID.
     */
    public function __construct(
        public string $sentence,
        public ?int    $bosTokenId,
        public ?int    $eosTokenId)
    {
        $this->len = strlen($sentence);
        $this->beginNodes = array_fill(0, $this->len + 1, []);
        $this->endNodes = array_fill(0, $this->len + 1, []);

        $bos = new TokenLatticeNode($this->bosTokenId, 0, 0, 0, 0.0);
        $eos = new TokenLatticeNode($this->eosTokenId, 1, $this->len, 0, 0.0);

        $this->nodes[] = $bos;
        $this->nodes[] = $eos;
        $this->beginNodes[$this->len][] = $eos;
        $this->endNodes[0][] = $bos;
    }

    /**
     * Inserts a new token node into the token lattice.
     *
     * @param int $pos The starting position of the token.
     * @param int $length The length of the token.
     * @param float $score The score of the token.
     * @param int $tokenId The token ID of the token.
     */
    public function insert(int $pos, int $length, float $score, int $tokenId): void
    {
        $nodeId = count($this->nodes);
        $node = new TokenLatticeNode($tokenId, $nodeId, $pos, $length, $score);
        $this->beginNodes[$pos][] = $node;
        $this->endNodes[$pos + $length][] = $node;
        $this->nodes[] = $node;
    }

    /**
     * Implements the Viterbi algorithm to compute the most likely sequence of tokens.
     *
     * @return TokenLatticeNode[] The array of nodes representing the most likely sequence of tokens.
     */
    public function viterbi(): array
    {
        $len = $this->len;
        $pos = 0;
        while ($pos <= $len) {
            if (empty($this->beginNodes[$pos])) {
                return [];
            }
            foreach ($this->beginNodes[$pos] as $rnode) {
                $rnode->prev = null;
                $bestScore = 0.0;
                $bestNode = null;
                foreach ($this->endNodes[$pos] as $lnode) {
                    $score = $lnode->backtraceScore + $rnode->score;
                    if ($bestNode === null || $score > $bestScore) {
                        $bestNode =  $lnode;
                        $bestScore = $score;
                    }
                }

                if ($bestNode !== null) {
                    $rnode->prev = $bestNode;
                    $rnode->backtraceScore = $bestScore;
                } else {
                    return [];
                }
            }
            ++$pos;
        }

        $results = [];
        $root = $this->beginNodes[$len][0];
        $prev = $root->prev;
        if ($prev === null) {
            return [];
        }

        $node = $prev;
        while ($node->prev !== null) {
            $results[] = $node;
            $n =  $node;
            $node =  $n->prev;
        }

        return array_reverse($results);
    }

    /**
     * @param TokenLatticeNode $node
     * @return string The array of nodes representing the most likely sequence of tokens.
     */
    public function piece(TokenLatticeNode $node): string
    {
        return substr($this->sentence, $node->pos, $node->length);
    }

    /**
     * @return array The array of nodes representing the most likely sequence of tokens.
     */
    public function tokens(): array
    {
        $nodes = $this->viterbi();
        return array_map([$this, 'piece'], $nodes);
    }

    /**
     * @return array The array of nodes representing the most likely sequence of tokens.
     */
    public function tokenIds(): array
    {
        $nodes = $this->viterbi();
        return array_map(fn($x) => $x->tokenId, $nodes);
    }
}
