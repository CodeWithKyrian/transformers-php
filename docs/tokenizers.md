---
outline: deep
---

# Tokenizers

Tokenizers are a crucial component of the TransformersPHP library. They are responsible for converting raw text inputs
into numerical inputs that can be understood by the models. This page provides a guide on how to use tokenizers in
TransformersPHP.

## Understanding Tokenization

Before going into the details of how to use tokenizers in TransformersPHP, it's important to understand what
tokenization is. A machine learning model cannot process raw text; it needs numerical inputs. Tokenization is the
process of breaking down a text into smaller units called tokens. These tokens are the basic
building blocks of the text that the model can understand.

Why don't we just split text into words, assign them numbers and call it a day, you may ask? Well, it's not that simple.
Text data is complex and diverse. If we were to try and represent all possible words in a language and assign them
numbers, we would end up with an enormous vocabulary (Oxford English Dictionary has over 170,000 words!!). This would
make the model very large and slow to train. With a good tokenization technique, we can reduce the size of the
vocabulary and make the model more efficient.

Let's use a simple example to illustrate tokenization. Consider the sentence "His transformation into a smart young man
was remarkable." One way to tokenize this sentence is to split it into words. The tokens would be:

```php
[
    'His', 'transformation', 'into', 'a', 'smart', 'young',  'man', 'was', 'remarkable'
]
```

and then the words would be assigned numerical IDs. For example, "His" could be assigned the ID 100, "transformation"
the
ID 101, and so on.

That was simple, right? But then if we have another sentence like "He transformed into a werewolf," we would have to
include the word "transformed" in our vocabulary. There are many other words that could be derived from the word
"transform.". That's where subword tokenization comes in. Instead of splitting the text into words, we can split it into
subwords. So a possible tokenization of the sentence "His transformation into a smart young man was remarkable" could be
as follows

```php
[
    'His', 'transform', '##ation', 'into', 'a', 'smart', 'young', 'man', 'was', 'remark', '##able'
]
```

Yes, it looks like we have more tokens now, but the vocabulary size is reduced because we can now represent words like
"transformation" and "remarkable" as a combination of subwords. The "transform" and "remark" can be reused in other
words that share the same root, as well as the "ation" and "able" suffixes. The `##` symbol indicates that the token is
a continuation of the previous token.

This is just one example of tokenization. There are many other tokenization techniques, such as byte-pair encoding (BPE)
and WordPiece, that are used in practice. Also, before the tokenization proper, the text undergoes some normalization
steps, such as lowercasing, removing special characters, and splitting contractions (e.g., "don't" to "do" and "n't").
This process is called pre-tokenization normalization.

## Using Tokenizers in TransformersPHP

Most models in the HuggingFace Hub, at least those compatible for transformers, have a `tokenizer.json` file that
contains the tokenization rules used by the model. These rules include things like the tokenization method to use, it's
vocabulary, the pre-tokenization normalization rules, and other tokenization parameters. Some even have a
`tokenizer_config.json` that contains even more tokenization parameters.

TransformersPHP tokenizers are designed to work seamlessly with these tokenization rules. They can tokenize text, pad
sequences, truncate sequences, and convert the tokenized text into tensors that can be understood by the models, using
those config files.

To use tokenizers in TransformersPHP, or even in isolated environments, you can use the provided `AutoTokenizer` class.

## Creating Tokenizer Instances

The `AutoTokenizer` class is used to create tokenizer instances in TransformersPHP. It's a versatile class that can load
any tokenizer from the Hugging Face model hub. Here's how to create a tokenizer instance using the `AutoTokenizer`
class:

```php
use Codewithkyrian\Transformers\PretrainedTokenizers\AutoTokenizer;

$tokenizer = AutoTokenizer::fromPretrained('Xenova/toxic-bert');
```

This downloads the tokenizer configs for the model and instantiates the tokenizer. The inputs of the `fromPretrained`
method are:

- `modelNameOrPath` *(string)*: The model identifier or the model path. It can be the model identifier or the model
  path.
- `cacheDir` *(string)* - The directory to cache the downloaded tokenizer files. It defaults to the option set in the
  global configuration.
- `revision` *(string)* - The specific version to use. It can be a branch name, a tag name, or a commit id. It
  to the `main` branch.
- `legacy` *(array)* - An array of legacy configurations to pass to the tokenizer. You may not necessarily need this.

## Tokenizer Inputs

To use the tokenizer, you can pass the text you want to tokenize as the first argument. The tokenizer can also accept
additional arguments to control the tokenization process. Here's an example of how to tokenize text using the tokenizer:

```php
$input = 'I hate you so much';
$encodedInput = $tokenizer($input, padding: true, truncation: true);
```

The inputs of the tokenizer are:

- `text` *(string|array)*: The text to tokenize. You can batch multiple texts by passing an array of strings. It's the
  only
  required argument.
- `textPair` *(string|array)*: The second text to tokenize. This is used when tokenizing text pairs, such as in
  question-answering tasks. If set, it must have the same length as the `text` argument.
- `padding` *(bool)*: Indicates whether to pad the sequences to the maximum length or not. It defaults to `false`.
  Padding
  is necessary when you want to batch multiple sequences together for inference, as the sequences must have the same
  length.
- `truncation` *(bool)*: Indicates whether to truncate the sequences to the maximum length or not. It defaults
  to `false`.
  Truncation is necessary when the sequences are longer than the maximum length accepted by the model.
- `maxLength` *(?int)*: The maximum length of the sequences. If set, the sequences will be truncated to this length.

## Tokenizer Outputs

The output of the tokenizer is an array, containing 3 tensors:

- `input_ids`: The tokenized text, converted to numerical IDs.
- `attention_mask`: A mask indicating which tokens are padding tokens and which are not. This is used by the model to
  ignore the padding tokens during inference.
- `token_type_ids`: A mask indicating which tokens belong to the first sentence and which belong to the second sentence.
  It's not always set, as it's only used in tasks that require tokenizing text pairs.

## Model Specific Tokenizers

While the `AutoTokenizer` class is very versatile, and internally resolves to the correct tokenizer
class, you can still skip that step and use the specific tokenizer class directly. This can be useful when you want to
have more control over the tokenization process. Here's an example of how to use the `BertTokenizer` class directly:

```php
use Codewithkyrian\Transformers\PretrainedTokenizers\BertTokenizer;

$tokenizer = BertTokenizer::fromPretrained('Xenova/toxic-bert');
```

The `fromPretrained` method of the `BertTokenizer` class accepts the same arguments as the `AutoTokenizer` class. You
can
see all available model specific tokenizers in the `Codewithkyrian\Transformers\PretrainedTokenizers` namespace.
