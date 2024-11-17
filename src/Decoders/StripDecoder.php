<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

class StripDecoder extends Decoder
{
    protected string $content;
    protected int $start;
    protected int $stop;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->content = $config['content'];
        $this->start = $config['start'];
        $this->stop = $config['stop'];
    }

    protected function decodeChain(array $tokens): array
    {
        return array_map(function ($token) {
            $startCut = 0;
            for ($i = 0; $i < $this->start; ++$i) {
                $char = mb_substr($token, $i, 1);
                if ($char === $this->content) {
                    $startCut = $i + 1;
                    continue;
                } else {
                    break;
                }
            }

            $stopCut = mb_strlen($token);
            for ($i = 0; $i < $this->stop; ++$i) {
                $index = mb_strlen($token) - $i - 1;
                if ($token[$index] ?? null === $this->content) {
                    $stopCut = $index;
                    continue;
                } else {
                    break;
                }
            }

            return mb_substr($token, $startCut, $stopCut - $startCut);
        }, $tokens);
    }
}
