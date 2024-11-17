<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTokenizers;

use function Codewithkyrian\Transformers\Utils\createPattern;

class SplitPreTokenizer extends PreTokenizer
{
    protected string|array $pattern;

    public function __construct(protected array $config)
    {
        $this->pattern = createPattern($config['pattern'], $config['invert']);
    }


    /**
     * Tokenizes text by splitting it using the given pattern.
     */
    public function preTokenizeText(string|array $text, array $options): array
    {
        if ($this->config['invert']) {
            preg_match_all("/$this->pattern/u", $text, $matches);
            return $matches[0];
        } else {
            $result = [];
            $offset = 0;

            preg_match_all("/$this->pattern/u", $text, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as $match) {
                $fullMatch = $match[0];
                $matchIndex = $match[1];

                if ($offset < $matchIndex) {
                    $result[] = substr($text, $offset, $matchIndex - $offset);
                }

                if (strlen($fullMatch) > 0) {
                    $result[] = $fullMatch;
                }

                $offset = $matchIndex + strlen($fullMatch);
            }

            if ($offset < strlen($text)) {
                $result[] = substr($text, $offset);
            }

            return $result;
        }
    }
}
