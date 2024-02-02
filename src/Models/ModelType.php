<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

enum ModelType: string
{
    case EncoderOnly = 'EncoderOnly';
    case EncoderDecoder = 'EncoderDecoder';
    case DecoderOnly = 'DecoderOnly';
    case Seq2Seq = 'Seq2Seq';
    case Vision2Seq = 'Vision2Seq';
    case MaskGeneration = 'MaskGeneration';
}