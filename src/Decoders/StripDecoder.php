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
                if ($token[$i] === $this->content) {
                    $startCut = $i + 1;
                    continue;
                } else {
                    break;
                }
            }

            $stopCut = strlen($token);
            for ($i = 0; $i < $this->stop; ++$i) {
                $index = strlen($token) - $i - 1;
                if ($token[$index] === $this->content) {
                    $stopCut = $index;
                    continue;
                } else {
                    break;
                }
            }

            return substr($token, $startCut, $stopCut - $startCut);
        }, $tokens);
    }
}