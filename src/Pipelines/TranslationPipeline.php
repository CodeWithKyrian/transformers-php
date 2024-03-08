<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

/**
 * Translates text from one language to another.
 *
 * *Example:** Multilingual translation w/ `Xenova/nllb-200-distilled-600M`.
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $translator = pipeline('translation', model: 'Xenova/nllb-200-distilled-600M');
 *
 * $output = $translator('जीवन एक चॉकलेट बॉक्स की तरह है।', srcLang: 'hin_Deva', tgtLang: 'fra_Latn'); // Hindi to French
 * // ['translation_text' => 'La vie est comme une boîte a chocolat.']
 * ```
 *
 * *Example:** Multilingual translation w/ `Xenova/m2m100_418M`.
 *
 * ```php
 * use function Codewithkyrian\Transformers\Pipelines\pipeline;
 *
 * $translator = pipeline('translation', model: 'Xenova/m2m100_418M');
 *
 * $output = $translator('生活就像一盒巧克力。', srcLang: 'zh', tgtLang: 'en'); // Chinese to English
 * // ['translation_text' => 'Life is like a box of chocolate.']
 * ```
 */
class TranslationPipeline extends Text2TextGenerationPipeline
{
    protected string $key = 'translation_text';
}