<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

ini_set('memory_limit', '-1');

//$transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-tiny.en');
$transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-base');
//$transcriber = pipeline('automatic-speech-recognition', 'Xenova/wav2vec2-large-xlsr-53-english');

$audioUrl = __DIR__ . '/../sounds/kyrian-dev.wav';
//$audioUrl = __DIR__ . '/../sounds/jfk.wav';
//$audioUrl = __DIR__ . '/../sounds/preamble.wav';
//$audioUrl = __DIR__ . '/../sounds/taunt.wav';
//$audioUrl = __DIR__ . '/../sounds/gettysburg.wav';
//$audioUrl = __DIR__ . '/../sounds/kyrian-speaking-30.wav';
//$audioUrl = __DIR__ . '/../sounds/kyrian-speaking.wav';
//$audioUrl = __DIR__ . '/../sounds/kyrian-speaking2.wav';
//$audioUrl = __DIR__ . '/../sounds/dataset1.wav';

$streamer = StdOutStreamer::make();
$output = $transcriber($audioUrl, maxNewTokens: 256, chunkLengthSecs: 20, returnTimestamps: 'word');

dd($output, timeUsage(), memoryUsage());