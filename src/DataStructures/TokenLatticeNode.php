<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\DataStructures;

class TokenLatticeNode
{
    /** @var TokenLatticeNode|null A reference to the previous node. */
    public ?TokenLatticeNode $prev = null;

    /** @var float The backtrace score. */
    public float $backtraceScore = 0.0;

    /**
     * Represents a node in a token lattice for a given sentence.
     * @param int $tokenId The ID of the token associated with this node.
     * @param int $nodeId The ID of this node.
     * @param int $pos The starting position of the token in the sentence.
     * @param int $length The length of the token.
     * @param float $score The score associated with the token.
     */
    public function __construct(
        public ?int   $tokenId,
        public int   $nodeId,
        public int   $pos,
        public int   $length,
        public float $score)
    {
    }

    /**
     * Returns a clone of this node.
     * @return TokenLatticeNode A clone of this node.
     */
    public function clone(): TokenLatticeNode
    {
        $n = new TokenLatticeNode($this->tokenId, $this->nodeId, $this->pos, $this->length, $this->score);
        $n->prev = $this->prev;
        $n->backtraceScore = $this->backtraceScore;
        return $n;
    }
}
