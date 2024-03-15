---
outline: deep
---

# Basic Usage

The quickest and most straightforward way to get started with Transformers PHP is through the pipelines API. If you're
familiar with the Transformers library for Python, you'll find this approach quite similar. It's a user-friendly API
that bundles a model with all the necessary preprocessing and postprocessing steps for a specific task.

## Creating a Pipeline

To create a pipeline, you need to specify the task you want to use it for. For example, if you want to use a pipeline
for sentiment analysis, you can create a pipeline like this:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$classifier = pipeline('sentiment-analysis');
```

The first time you run this, Transformers PHP will download and cache the default pre-trained model for sentiment
analysis on-the-fly. This initial setup might take a bit, but subsequent runs will be much faster.

> [!TIP]
> To avoid any wait time or possible hiccups with on-the-fly model downloads, it's a good idea to pre-download
> your models. Check out the section on [pre-downloading models](/getting-started#pre-download-models) for how to
> do this.

## Using a different model

Each task has a default model it uses for inference. You can however specify a different model to use:

```php
$classifier = pipeline('sentiment-analysis', 'Xenova/bert-base-multilingual-uncased-sentiment');
```

You can also specify if the quantized model should be used or not (the default is `true`):

```php
$classifier = pipeline('sentiment-analysis', quantized: false);
```

## Using the Pipeline

Now that you have your pipeline, using it is as simple as calling a function. Just provide the text you want to analyze:

```php
$result = $classifier('I love Transformers PHP!');
```

And voilà, you'll get the sentiment analysis result:

```php
['label' => 'POSITIVE',  'score' => 0.9995358059835]
```

You're not limited to one text at a time; you can also pass an array of texts to get multiple analyses:

```php
$results = $classifier([
    'I love Transformers PHP!',
    'I hate Transformers PHP!',
]);
```

The output will give you a sentiment score for each text:

```php
[
    ['label' => 'POSITIVE',  'score' => 0.99980061678407],
    ['label' => 'NEGATIVE',  'score' => 0.99842234422764],
]
```

## What's Next?

Now that you've seen how easy it is to use Transformers PHP, you might want to explore the other features it offers.
Check out the advanced usage section to learn about more advanced features like customizing the
pipelines, using the models directly, using tokenizers, and more.