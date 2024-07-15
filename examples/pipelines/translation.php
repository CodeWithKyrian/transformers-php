<?php

declare(strict_types=1);

require_once './bootstrap.php';

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);

//$translator = pipeline('translation', 'Xenova/m2m100_418M');
$translator = pipeline('translation', 'Xenova/nllb-200-distilled-600M');

$streamer = StdOutStreamer::make();

//$output = $translator('生活就像一盒巧克力。', streamer: $streamer, tgtLang: 'en');
$output = $translator('जीवन एक चॉकलेट बॉक्स की तरह है।', streamer: $streamer, tgtLang: 'fra_Latn');
//$output = $translator('जीवन एक चॉकलेट बॉक्स की तरह है।', streamer: $streamer, tgtLang: 'fr');
//$output = $translator('संयुक्त राष्ट्र के प्रमुख का कहना है कि सीरिया में कोई सैन्य समाधान नहीं है', streamer: $streamer, tgtLang: 'fr', maxNewTokens: 256);

dd("done", timeUsage(), memoryUsage());
