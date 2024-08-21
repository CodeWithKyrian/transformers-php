<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Generation\Streamers\TextStreamer;
use Codewithkyrian\Transformers\Generation\Streamers\WhisperTextStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\{memoryUsage, timeUsage};

require_once './bootstrap.php';

ini_set('memory_limit', '-1');

$transcriber = pipeline('asr', 'Xenova/whisper-tiny.en');
//$transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-tiny');
//$transcriber = pipeline('automatic-speech-recognition', 'Xenova/whisper-base');
//$transcriber = pipeline('automatic-speech-recognition', 'Xenova/wav2vec2-large-xlsr-53-english');

$audioUrl = __DIR__ . '/../sounds/kyrian-dev.wav';
$audioUrl = __DIR__ . '/../sounds/jfk.wav';
$audioUrl = __DIR__ . '/../sounds/preamble.wav';
//$audioUrl = __DIR__ . '/../sounds/taunt.wav';
//$audioUrl = __DIR__ . '/../sounds/gettysburg.wav';
//$audioUrl = __DIR__ . '/../sounds/kyrian-speaking.wav';
//$audioUrl = __DIR__ . '/../sounds/ted_60.wav';
//$audioUrl = __DIR__ . '/../sounds/sample-1.mp3';


$output = $transcriber($audioUrl,
    maxNewTokens: 256,
    chunkLengthSecs: 24,
//    returnTimestamps: 'word',
);

dd($output, timeUsage(), memoryUsage());