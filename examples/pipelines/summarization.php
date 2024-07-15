<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Generation\Streamers\TextStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\{memoryUsage, timeUsage};

require_once './bootstrap.php';


ini_set('memory_limit', -1);


$summarizer = pipeline('summarization', 'Xenova/distilbart-cnn-6-6');

$streamer = TextStreamer::make();

$article = 'The tower is 324 metres (1,063 ft) tall, about the same height as an 81-storey building, ' .
    'and the tallest structure in Paris. Its base is square, measuring 125 metres (410 ft) on each side. ' .
    'During its construction, the Eiffel Tower surpassed the Washington Monument to become the tallest ' .
    'man-made structure in the world, a title it held for 41 years until the Chrysler Building in New ' .
    'York City was finished in 1930. It was the first structure to reach a height of 300 metres. Due to ' .
    'the addition of a broadcasting aerial at the top of the tower in 1957, it is now taller than the ' .
    'Chrysler Building by 5.2 metres (17 ft). Excluding transmitters, the Eiffel Tower is the second-tallest ' .
    'free-standing structure in France after the Millau Viaduct.';


//$article = "I called my friend to see if he wanted to go to the movies. However, he was busy and said he would " .
//    "call me back. I didn't hear back from him, so I called him again. He didn't answer, so I decided to go to the movies by myself.";

$summary = $summarizer($article, streamer: $streamer, maxNewTokens: 512, temperature: 0.7);

dd("Done", timeUsage(), memoryUsage());