---
outline: deep
---

# Introduction

## What is Transformers PHP

Transformers PHP is a toolkit for PHP developers to add machine learning magic to their projects easily. You've probably
heard about the Python library from Hugging Face, famous for doing awesome stuff with text, like summarizing long
articles, translating between languages, and even image and audio related tasks. Transformers
PHP brings this capability to the PHP world.

### Using Pre-trained Models

The core idea behind Transformers PHP is to let you use models that are already trained. "Pre-trained models" are just
machine learning models that have been fed and learned from massive amounts of text data. They're ready to go out of the
box and can perform a wide range of tasks. With Transformers PHP, these models run directly in your PHP application.
That means you don't need to use external services or APIs to process your data. Everything happens locally, on your
server.

### What's ONNX Runtime?

ONNX Runtime might seem like a complex term, but it's essentially a high-performance engine designed for both
inferring and accelerating machine learning models. The Open Neural Network Exchange (ONNX) format is at the heart of
this engine, serving as a universal format for machine learning models. This means no matter which framework was
originally used to train a model — be it PyTorch, TensorFlow, JAX, or even classical machine learning libraries like
scikit-learn, LightGBM, XGBoost, etc.—it can be converted to ONNX format. This format can run efficiently across
different platforms, including your PHP applications.

### Inspired by the Best

The development of Transformers PHP was inspired by the [Xenova/transformers](https://github.com/xenova/transformers.js)
project, a similar initiative for JavaScript using ONNX runtime too. This shared inspiration means that most models
prepared for use with [Xenova/transformers](https://github.com/xenova/transformers.js), are also compatible with
Transformers PHP. It creates a seamless bridge between the machine learning world and PHP development, allowing you to
leverage powerful models within your applications.

## Quick tour

To make things clear, let's compare how you'd do something in Python, PHP (using our library), and Javascript (using a
similar library called Xenova). Let's say you want to find out if a piece of text has a positive or negative vibe:

<table>
<tr>

<th align="center"><b>Python (original)</b></th>
<th align="center"><b>PHP (ours)</b></th>
<th align="center"><b>Javascript (Xenova)</b></th>

</tr>

<tr>
<td>

```python
from transformers import pipeline

# Allocate a pipeline for sentiment-analysis
pipe = pipeline('sentiment-analysis')

out = pipe('I love transformers!')
# [{'label': 'POSITIVE', 'score': 0.999806941}]
```

</td>
<td>

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

// Allocate a pipeline for sentiment-analysis
$pipe = pipeline('sentiment-analysis');

$out = $pipe('I love transformers!');
// [{'label': 'POSITIVE', 'score': 0.999808732}]
```

</td>
<td>

```javascript
import {pipeline} from '@xenova/transformers';

// Allocate a pipeline for sentiment-analysis
let pipe = await pipeline('sentiment-analysis');

let out = await pipe('I love transformers!');
// [{'label': 'POSITIVE', 'score': 0.999817686}]
```

</td>
</tr>
</table>

You can see how similar it is across languages, making it easier if you're switching between them or learning a new one.

## What Transformers PHP is Not

While the original HuggingFace Transformers library in Python is a versatile tool supporting both the training of
machine learning models and inference (using models to make predictions), Transformers PHP allows only inference. This
means that you cannot train new models from scratch, or fine-tune pretrained models using Transformers PHP.