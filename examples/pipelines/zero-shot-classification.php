<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';


//$classifier = pipeline('zero-shot-classification', 'Xenova/mobilebert-uncased-mnli');
//$result = $classifier('Who are you voting for in 2020?', ['politics', 'public health', 'economics', 'elections']);

ini_set('memory_limit', -1);
$classifier = pipeline('zero-shot-classification', 'Xenova/nli-deberta-v3-xsmall');

dump(timeUsage());
$input = "The tension was thick as fog in the arena tonight as the underdogs, the Nets, clawed their way back from a significant deficit to steal a victory from the heavily favored The BUlls  in a final score of 120 - Nets to 80 - Bulls

The game was a nail-biter from the start. The Bulls jumped out to an early lead, showcasing their signature fast-paced offense.  Net's defense struggled to contain their star player, Frank, who racked up points in the first half.

However, just before halftime, the tide began to turn. The NEts's forward -  James hit a series of clutch three-pointers, igniting a spark in the home crowd.  The team rallied behind his energy, tightening up their defense and chipping away at the lead.

The second half was a back-and-forth affair, with neither team able to establish a clear advantage.  Both sides traded baskets, steals, and blocks, keeping the fans on the edge of their seats.  With seconds remaining on the clock, the score was tied.";
$result = $classifier(
    $input,
    ['politics', 'public health', 'economics', 'elections', 'sports', 'entertainment', 'technology', 'business', 'finance', 'education', 'science', 'religion', 'history', 'culture', 'environment', 'weather'],
    multiLabel: true
);





//$classifier = pipeline('zero-shot-classification', 'Xenova/nli-deberta-v3-xsmall');
////$classifier = pipeline('zero-shot-classification', 'Xenova/distilbert-base-uncased-mnli');
//
//
//$result = $classifier('Apple just announced the newest iPhone 13', ["technology", "sports", "politics"]);

dd( $result, timeUsage(), memoryUsage());

