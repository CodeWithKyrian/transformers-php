<p align="center">
    <br/>
    <picture> 
        <source media="(prefers-color-scheme: dark)" srcset="https://huggingface.co/datasets/Xenova/transformers.js-docs/raw/main/transformersjs-dark.svg" width="500" style="max-width: 100%;">
        <source media="(prefers-color-scheme: light)" srcset="https://huggingface.co/datasets/Xenova/transformers.js-docs/raw/main/transformersjs-light.svg" width="500" style="max-width: 100%;">
        <img alt="transformers.js javascript library logo" src="https://huggingface.co/datasets/Xenova/transformers.js-docs/raw/main/transformersjs-light.svg" width="500" style="max-width: 100%;">
    </picture>
    <br/>
</p>

<h3 align="center">
    <p>State-of-the-art Machine Learning for PHP</p>
</h3>

Transformers PHP is designed to be functionally equivalent to the Python library, while still maintaining the same level
of performance and ease of use. This library is built on top of the Hugging Face's Transformers library, which provides
thousands of pre-trained models in 100+ languages. It is designed to be a simple and easy-to-use library for PHP
developers using a similar API to the Python library. These models can be used for a variety of tasks, including text
generation, summarization, translation, and more.

Transformers PHP uses [ONNX Runtime](https://onnxruntime.ai/) to run the models, which is a high-performance scoring
engine for Open Neural Network Exchange (ONNX) models. You can easily convert any PyTorch or TensorFlow model to ONNX
and use it with Transformers PHP using [🤗 Optimum](https://github.com/huggingface/optimum#onnx--onnx-runtime).

## Quick tour

Because Transformers PHP is designed to be functionally equivalent to the Python library, it's super easy to learn from
existing Python or Javascript code. We provide the `pipeline` API, which is a high-level, easy-to-use API that groups
together a model with its necessary preprocessing and postprocessing steps.

<table>
<tr>
<th align="center"><b>Python (original)</b></th>

[//]: # (<th align="center"><b>Javascript &#40;Xenova&#41;</b></th>)
<th align="center"><b>PHP (ours)</b></th>

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

[//]: # (<td>)

[//]: # ()

[//]: # (```javascript)

[//]: # (import { pipeline } from '@xenova/transformers';)

[//]: # ()

[//]: # (// Allocate a pipeline for sentiment-analysis)

[//]: # (let pipe = await pipeline&#40;'sentiment-analysis'&#41;;)

[//]: # ()

[//]: # (let out = await pipe&#40;'I love transformers!'&#41;;)

[//]: # (// [{'label': 'POSITIVE', 'score': 0.999817686}])

[//]: # (```)

[//]: # ()

[//]: # (</td>)
<td>

```php
use function Codewithkyrian\Transformers\pipeline;

// Allocate a pipeline for sentiment-analysis
$pipe = pipeline('sentiment-analysis');

$out = $pipe('I love transformers!');
// [{'label': 'POSITIVE', 'score': 0.999808732}]
```

</tr>
</table>

You can also use a different model by specifying the model id or path as the second argument to the `pipeline` function.
For example:

```php
use function Codewithkyrian\Transformers\pipeline;

// Allocate a pipeline for translation
$pipe = pipeline('translation', 'Xenova/distilbert-base-uncased-finetuned-sst-2-english');

```

## Installation

You can install the library via Composer. This is the recommended way to install the library.

```bash
composer require codewithkyrian/transformers
```

Next, you must run the installation/initialize command to download the shared library necessary to run the ONNX models

```bash
./vendor/bin/transformers install
```

## Usage

By default, Transformers PHP uses hosted pretrained ONNX models. For supported tasks, models that have been converted to
work with [Xenova's Transformers.js](https://huggingface.co/models?library=transformers.js) on HuggingFace should work
out of the box with Transformers PHP.

## Configuration

You can configure the behaviour of the Transformers PHP library as follows:

```php
use Codewithkyrian\Transformers\Transformers;

Transformers::configure()
    ->setCacheDir('...') // Set the default cache directory for transformers models. Defaults to `models`
    ->setRemoteHost('...') // Set the remote host for downloading models. Defaults to `https://huggingface.co`
    ->setRemotePathTemplate('...'); // Set the remote path template for downloading models. Defaults to `{model}/resolve/{revision}/{file}`
```

You can call the `set` methods in any order, or leave any out entirely, in which case, it uses the default values.

### Convert your models to ONNX

Transformers PHP only works with ONNX models, therefore, you must convert your PyTorch, TensorFlow or JAX models to
ONNX. It is recommended to use [🤗 Optimum](https://huggingface.co/docs/optimum) to perform the conversion and
quantization of your model.

## Supported tasks/models

This package is a WIP, but here's a list of tasks and architectures currently tested and supported by Transformers PHP.

### Tasks

#### Natural Language Processing

| Task                                                                                                   | ID                                            | Description                                                                                    | Supported? |
|--------------------------------------------------------------------------------------------------------|-----------------------------------------------|------------------------------------------------------------------------------------------------|------------|
| [Conversational](https://huggingface.co/tasks/conversational)                                          | `conversational`                              | Generating conversational text that is relevant, coherent and knowledgable given a prompt.     | ❌          |
| [Fill-Mask](https://huggingface.co/tasks/fill-mask)                                                    | `fill-mask`                                   | Masking some of the words in a sentence and predicting which words should replace those masks. | ✅          |
| [Question Answering](https://huggingface.co/tasks/question-answering)                                  | `question-answering`                          | Retrieve the answer to a question from a given text.                                           | ✅          |
| [Sentence Similarity](https://huggingface.co/tasks/sentence-similarity)                                | `sentence-similarity`                         | Determining how similar two texts are.                                                         | ✅          |
| [Summarization](https://huggingface.co/tasks/summarization)                                            | `summarization`                               | Producing a shorter version of a document while preserving its important information.          | ❌          |
| [Table Question Answering](https://huggingface.co/tasks/table-question-answering)                      | `table-question-answering`                    | Answering a question about information from a given table.                                     | ❌          |
| [Text Classification](https://huggingface.co/tasks/text-classification)                                | `text-classification` or `sentiment-analysis` | Assigning a label or class to a given text.                                                    | ✅          |
| [Text Generation](https://huggingface.co/tasks/text-generation#completion-generation-models)           | `text-generation`                             | Producing new text by predicting the next word in a sequence.                                  | ❌          |
| [Text-to-text Generation](https://huggingface.co/tasks/text-generation#text-to-text-generation-models) | `text2text-generation`                        | Converting one text sequence into another text sequence.                                       | ❌          |
| [Token Classification](https://huggingface.co/tasks/token-classification)                              | `token-classification` or `ner`               | Assigning a label to each token in a text.                                                     | ❌          |
| [Translation](https://huggingface.co/tasks/translation)                                                | `translation`                                 | Converting text from one language to another.                                                  | ❌          |
| [Zero-Shot Classification](https://huggingface.co/tasks/zero-shot-classification)                      | `zero-shot-classification`                    | Classifying text into classes that are unseen during training.                                 | ✅          |

#### Vision

| Task                                                                                          | ID                     | Description                                                                                                                                                                             | Supported? |
|-----------------------------------------------------------------------------------------------|------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| [Depth Estimation](https://huggingface.co/tasks/depth-estimation)                             | `depth-estimation`     | Predicting the depth of objects present in an image.                                                                                                                                    | ❌          |
| [Image Classification](https://huggingface.co/tasks/image-classification)                     | `image-classification` | Assigning a label or class to an entire image.                                                                                                                                          | ❌          |
| [Image Segmentation](https://huggingface.co/tasks/image-segmentation)                         | `image-segmentation`   | Divides an image into segments where each pixel is mapped to an object. This task has multiple variants such as instance segmentation, panoptic segmentation and semantic segmentation. | ❌          |
| [Image-to-Image](https://huggingface.co/tasks/image-to-image)                                 | `image-to-image`       | Transforming a source image to match the characteristics of a target image or a target image domain.                                                                                    | ❌          |
| [Mask Generation](https://huggingface.co/tasks/mask-generation)                               | `mask-generation`      | Generate masks for the objects in an image.                                                                                                                                             | ❌          |
| [Object Detection](https://huggingface.co/tasks/object-detection)                             | `object-detection`     | Identify objects of certain defined classes within an image.                                                                                                                            | ❌          |
| [Video Classification](https://huggingface.co/tasks/video-classification)                     | N/A                    | Assigning a label or class to an entire video.                                                                                                                                          | ❌          |
| [Unconditional Image Generation](https://huggingface.co/tasks/unconditional-image-generation) | N/A                    | Generating images with no condition in any context (like a prompt text or another image).                                                                                               | ❌          |

#### Audio

| Task                                                                                      | ID                                  | Description                                          | Supported? |
|-------------------------------------------------------------------------------------------|-------------------------------------|------------------------------------------------------|------------|
| [Audio Classification](https://huggingface.co/tasks/audio-classification)                 | `audio-classification`              | Assigning a label or class to a given audio.         | ❌          |
| [Audio-to-Audio](https://huggingface.co/tasks/audio-to-audio)                             | N/A                                 | Generating audio from an input audio source.         | ❌          |
| [Automatic Speech Recognition](https://huggingface.co/tasks/automatic-speech-recognition) | `automatic-speech-recognition`      | Transcribing a given audio into text.                | ❌          |
| [Text-to-Speech](https://huggingface.co/tasks/text-to-speech)                             | `text-to-speech` or `text-to-audio` | Generating natural-sounding speech given text input. | ❌          |

#### Tabular

| Task                                                                          | ID  | Description                                                         | Supported? |
|-------------------------------------------------------------------------------|-----|---------------------------------------------------------------------|------------|
| [Tabular Classification](https://huggingface.co/tasks/tabular-classification) | N/A | Classifying a target category (a group) based on set of attributes. | ❌          |
| [Tabular Regression](https://huggingface.co/tasks/tabular-regression)         | N/A | Predicting a numerical value given a set of attributes.             | ❌          |

#### Multimodal

| Task                                                                                                                                      | ID                               | Description                                                                                                                   | Supported? |
|-------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------|-------------------------------------------------------------------------------------------------------------------------------|------------|
| [Document Question Answering](https://huggingface.co/tasks/document-question-answering)                                                   | `document-question-answering`    | Answering questions on document images.                                                                                       | ❌          |
| [Feature Extraction](https://huggingface.co/tasks/feature-extraction)                                                                     | `feature-extraction`             | Transforming raw data into numerical features that can be processed while preserving the information in the original dataset. | ✅          |
| [Image-to-Text](https://huggingface.co/tasks/image-to-text)                                                                               | `image-to-text`                  | Output text from a given image.                                                                                               | ❌          |
| [Text-to-Image](https://huggingface.co/tasks/text-to-image)                                                                               | `text-to-image`                  | Generates images from input text.                                                                                             | ❌          |
| [Visual Question Answering](https://huggingface.co/tasks/visual-question-answering)                                                       | `visual-question-answering`      | Answering open-ended questions based on an image.                                                                             | ❌          |
| [Zero-Shot Audio Classification](https://huggingface.co/learn/audio-course/chapter4/classification_models#zero-shot-audio-classification) | `zero-shot-audio-classification` | Classifying audios into classes that are unseen during training.                                                              | ❌          |
| [Zero-Shot Image Classification](https://huggingface.co/tasks/zero-shot-image-classification)                                             | `zero-shot-image-classification` | Classifying images into classes that are unseen during training.                                                              | ❌          |
| [Zero-Shot Object Detection](https://huggingface.co/tasks/zero-shot-object-detection)                                                     | `zero-shot-object-detection`     | Identify objects of classes that are unseen during training.                                                                  | ❌          |

#### Reinforcement Learning

| Task                                                                          | ID  | Description                                                                                                                                | Supported? |
|-------------------------------------------------------------------------------|-----|--------------------------------------------------------------------------------------------------------------------------------------------|------------|
| [Reinforcement Learning](https://huggingface.co/tasks/reinforcement-learning) | N/A | Learning from actions by interacting with an environment through trial and error and receiving rewards (negative or positive) as feedback. | ❌          |

### Models

1. **[ALBERT](https://huggingface.co/docs/transformers/model_doc/albert)** (from Google Research and the Toyota Technological Institute at Chicago) released with the paper [ALBERT: A Lite BERT for Self-supervised Learning of Language Representations](https://arxiv.org/abs/1909.11942), by Zhenzhong Lan, Mingda Chen, Sebastian Goodman, Kevin Gimpel, Piyush Sharma, Radu Soricut.
1. **[BERT](https://huggingface.co/docs/transformers/model_doc/bert)** (from Google) released with the paper [BERT: Pre-training of Deep Bidirectional Transformers for Language Understanding](https://arxiv.org/abs/1810.04805) by Jacob Devlin, Ming-Wei Chang, Kenton Lee, and Kristina Toutanova.
1. **[BERT For Sequence Generation](https://huggingface.co/docs/transformers/model_doc/bert-generation)** (from Google) released with the paper [Leveraging Pre-trained Checkpoints for Sequence Generation Tasks](https://arxiv.org/abs/1907.12461) by Sascha Rothe, Shashi Narayan, Aliaksei Severyn.
1. **[BERTweet](https://huggingface.co/docs/transformers/model_doc/bertweet)** (from VinAI Research) released with the paper [BERTweet: A pre-trained language model for English Tweets](https://aclanthology.org/2020.emnlp-demos.2/) by Dat Quoc Nguyen, Thanh Vu and Anh Tuan Nguyen.
1. **[BigBird-Pegasus](https://huggingface.co/docs/transformers/model_doc/bigbird_pegasus)** (from Google Research) released with the paper [Big Bird: Transformers for Longer Sequences](https://arxiv.org/abs/2007.14062) by Manzil Zaheer, Guru Guruganesh, Avinava Dubey, Joshua Ainslie, Chris Alberti, Santiago Ontanon, Philip Pham, Anirudh Ravula, Qifan Wang, Li Yang, Amr Ahmed.
1. **[BigBird-RoBERTa](https://huggingface.co/docs/transformers/model_doc/big_bird)** (from Google Research) released with the paper [Big Bird: Transformers for Longer Sequences](https://arxiv.org/abs/2007.14062) by Manzil Zaheer, Guru Guruganesh, Avinava Dubey, Joshua Ainslie, Chris Alberti, Santiago Ontanon, Philip Pham, Anirudh Ravula, Qifan Wang, Li Yang, Amr Ahmed.
1. **[ConvBERT](https://huggingface.co/docs/transformers/model_doc/convbert)** (from YituTech) released with the paper [ConvBERT: Improving BERT with Span-based Dynamic Convolution](https://arxiv.org/abs/2008.02496) by Zihang Jiang, Weihao Yu, Daquan Zhou, Yunpeng Chen, Jiashi Feng, Shuicheng Yan.
1. **[DeBERTa](https://huggingface.co/docs/transformers/model_doc/deberta)** (from Microsoft) released with the paper [DeBERTa: Decoding-enhanced BERT with Disentangled Attention](https://arxiv.org/abs/2006.03654) by Pengcheng He, Xiaodong Liu, Jianfeng Gao, Weizhu Chen.
1. **[DeBERTa-v2](https://huggingface.co/docs/transformers/model_doc/deberta-v2)** (from Microsoft) released with the paper [DeBERTa: Decoding-enhanced BERT with Disentangled Attention](https://arxiv.org/abs/2006.03654) by Pengcheng He, Xiaodong Liu, Jianfeng Gao, Weizhu Chen.
1. **[ELECTRA](https://huggingface.co/docs/transformers/model_doc/electra)** (from Google Research/Stanford University) released with the paper [ELECTRA: Pre-training text encoders as discriminators rather than generators](https://arxiv.org/abs/2003.10555) by Kevin Clark, Minh-Thang Luong, Quoc V. Le, Christopher D. Manning.
1. **[RoBERTa](https://huggingface.co/docs/transformers/model_doc/roberta)** (from Facebook), released together with the paper [RoBERTa: A Robustly Optimized BERT Pretraining Approach](https://arxiv.org/abs/1907.11692) by Yinhan Liu, Myle Ott, Naman Goyal, Jingfei Du, Mandar Joshi, Danqi Chen, Omer Levy, Mike Lewis, Luke Zettlemoyer, Veselin Stoyanov.
1. **[RoBERTa-PreLayerNorm](https://huggingface.co/docs/transformers/model_doc/roberta-prelayernorm)** (from Facebook) released with the paper [fairseq: A Fast, Extensible Toolkit for Sequence Modeling](https://arxiv.org/abs/1904.01038) by Myle Ott, Sergey Edunov, Alexei Baevski, Angela Fan, Sam Gross, Nathan Ng, David Grangier, Michael Auli.




