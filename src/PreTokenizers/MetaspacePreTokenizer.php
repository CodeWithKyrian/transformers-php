<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

/**
 * This PreTokenizer replaces spaces with the given replacement character, adds a prefix space if requested,
 *  and returns a list of tokens.
 */
class MetaspacePreTokenizer extends PreTokenizer
{
    /**
     * Whether to add a prefix space to the first token.
     */
    protected bool $addPrefixSpace;

    /**
     * The character to replace spaces with.
     */
    protected string $replacement;

    /**
     * optional string representation of the replacement character.
     */
    protected string $strRep;

    /**
     * The metaspace prepending scheme.
     */
    protected string $prependScheme;

    public function __construct(protected array $config)
    {
        $this->addPrefixSpace = $this->config['add_prefix_space'] ?? false;
        $this->replacement = $this->config['replacement'];
        $this->strRep = $this->config['str_rep'] ?? $this->replacement;
        $this->prependScheme = $this->config['prepend_scheme'] ?? 'always';
    }


    /**
     * This method takes a string, replaces spaces with the replacement character,
     *  adds a prefix space if requested, and returns a new list of tokens.
     * @param string|array $text
     * @param array{ section_index : int} $options
     * @return array|string[]
     */
    public function preTokenizeText(string|array $text, array $options): array
    {
        $normalized = str_replace(' ', $this->strRep, $text);

        $sectionIndex = $options['section_index'] ?? null;

        if (
            // We add a prefix space if:
            //  (1) The addPrefixSpace option is enabled and the normalized
            //      token does not already start with the replacement character.
            ($this->addPrefixSpace && !str_starts_with($normalized, $this->replacement))

            // and (2) either:
            //  (a) prepend_scheme is 'always'
            //  (b) prepend_scheme is 'first' and this is the first section
            && (
                $this->prependScheme === 'always' ||
                ($this->prependScheme === 'first' && $sectionIndex === 0)
            )
        ) {
            $normalized = $this->strRep . $normalized;
        }

        // Return as an array
        return [$normalized];
    }
}