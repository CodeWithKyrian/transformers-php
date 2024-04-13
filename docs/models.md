---
outline: deep
---

# Models

The easiest and most straightforward way of using TransformerPHP is through the `pipeline` helper, which streamlines
preprocessing and post-processing of inputs and outputs. However, there are times when you may need more control over
the process. In such cases, you can use the models directly. This page provides a guide on how to use the models
directly.

## Creating Model Instances

To instantiate a model, TransformersPHP offers the versatile `AutoModel` class, capable of loading any model from the
Hugging Face model hub. It's important to note that when using models directly, they are not restricted to a specific
task; they can be used for any compatible task.

```php
use Codewithkyrian\Transformers\Models\Auto\AutoModel;

$model = AutoModel::fromPretrained('Xenova/bert-base-uncased');
```

The `fromPretrained` method is used to load a model from the Hugging Face model hub. It accepts the model name
as it's primary argument. Just like in pipelines, the model name can be the model identifier or the model path.
Here's a full list of all the arguments that can be passed to the `fromPretrained` method:

- `modelNameOrPath` *(string)*: The model identifier or the model path. It can be the model identifier or the model
  path.
- `quantized` *(bool)*: Indicates whether to use the quantized version of the model or not. It defaults to `false`
- `config` *(array)* - Allows you to pass a custom configuration for the pipeline. This could include specific model
  parameters or preprocessing options.
- `cacheDir` *(string)* - The directory to cache the model weights and configuration. It defaults to the option set
  in the global configuration
- `revision` *(string)* - The specific model version to use. It can be a branch name, a tag name, or a commit id. It
  to the `main` branch.
- `modelFilename` *(string)* - The filename of the model in the repository. If not provided, it's inferred from
  the type of model being loaded.

## Model Invocation and Preprocessing

Once instantiated, the model can be invoked with inputs for inference. However, unlike the pipeline that accepts
a text or image input, the model expects numerical inputs (usually tensors). This means that whatever input you want to
pass to the model must undergo preprocessing to convert it to a numerical form that the model can understand. Thus, the
AutoModel class scarcely works alone. For preprocessing, the AutoModel class collaborates with `Autotokenizer`
and `AutoProcessor` classes.

Here's an example of how to use the `AutoModel` class to perform inference on a model:

```php
use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Tokenizers\AutoTokenizer;

$tokenizer = AutoTokenizer::fromPretrained('Xenova/toxic-bert');
$model = AutoModel::fromPretrained('Xenova/toxic-bert');

$input = 'I hate you so much';
$encodedInput = $tokenizer($input, padding: true, truncation: true);

$output = $model($encodedInput);
```

## Postprocessing Model Outputs

If you inspect the output of the model from the above example, you'll notice that it's not the final output you may be
looking for. The output is a multidimensional array containing the model outputs. You may need to post-process the
outputs to get the final results you're looking for. The post-processing of the outputs is highly dependent on the
model and the task it's performing.

For example, for classification tasks, post-processing is straightforward - applying a softmax function to the logits
to get the probabilities of each class, and then mapping the class ids to their respective labels.

```php

$id2label = $model->config['id2label'];

$probabilities = $output['logits'][0]->softmax();
 
foreach ($probabilities as $labelId => $score) {
  echo $id2label[$labelId] . ': ' . $score . PHP_EOL;
}
```

Output:

```bash
toxic: 0.98505208976127
severe_toxic: 0.00054822923280057
obscene: 0.0014310951235518
threat: 0.0036767840868768
insult: 0.0082214922365694
identity_hate: 0.0010703095589325
```

> [!NOTE]
> The softmax function is part of the `Tensor` class, a utility class used severally in TransformersPHP to
> perform matrix operations. Checkout the [Tensor documentation](/utils/tensor) for more information on how to use it.

The post-processing of the model outputs can be more complex for other tasks. Be sure to consult the model card for
the model you're using to understand how to post-process the outputs (most will contain examples in Python, but you can
easily translate them to PHP).

## Task Specific Auto Models

While `AutoModel` is good for general use cases, it may not be the best choice for all tasks. For simpler encoder-only
models, it may work just fine. But for more complex models that require additional configurations or preprocessing,
`AutoModel` may not be sufficient since it won't load them. For such cases, TransformersPHP provides several
task-specific
`AutoModel` classes. These classes contain some validation to ensure that the model you're loading can actually be used
for the task the `AutoModel` class was designed for. Here are some of the task-specific `AutoModel` classes available:

- `AutoModelForCasualLM`
- `AutoModelForImageClassification`
- `AutoModelForImageFeatureExtraction`
- `AutoModelForImageToImage`
- `AutoModelForMaskedLM`
- `AutoModelForObjectDetection`
- `AutoModelForQuestionAnswering`
- `AutoModelForSeq2SeqLM`
- `AutoModelForSequenceClassification`
- `AutoModelForTokenClassification`
- `AutoModelForVision2Seq`
- `AutoModelForZeroShotObjectDetection`

These classes all have the same `fromPretrained` method as the `AutoModel` class, and they work similarly. During
instantiation, they load the model and it's required configurations.

Besides validation, one other advantage these task specific models offer is typed output. The output of the `AutoModel`
is just an array, but for the task specific models, the output is typed. This means you can get the output of the model
as an object with properties that you can access directly, depending on the task.

For example, for the classification task above, it can be modified to use the `AutoModelForSequenceClassification` class
like so:

```php
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSequenceClassification;

$model = AutoModel::fromPretrained('Xenova/toxic-bert'); // [!code --]
$model = AutoModelForSequenceClassification::fromPretrained('Xenova/toxic-bert'); // [!code ++]

// ....

$probabilities = $output['logits'][0]->softmax(); // [!code --]
$probabilities = $output->logits[0]->softmax(); // [!code ++]

// ...

```

For the `AutoModelForSequenceClassification` class, the output is an object with a `logits` property that contains the
model outputs. You can easily find out the available properties for any auto model class by inspecting the source code
of the model class, or by using an IDE with intellisense.

## Using Model-Specific Classes

When going through various model cards, you may have observed that certain models opt for even more specific classes
than the
task-specific auto model classes in their code examples. These examples use dedicated model classes for specific tasks
such as `BertForSequenceClassification`, `RoFormerForMaskedLM`, `BertForTokenClassification`, etc. TransformersPHP
provides these classes as well. Actually, these are the classes being used behind the scenes when you use the `pipeline`
helper function. They can be found in the `Codewithkyrian\Transformers\Models\Pretrained` namespace. They are used in
the same way as the auto model classes, including the `fromPretrained` method.

```php
use Codewithkyrian\Transformers\Models\Pretrained\BertForSequenceClassification;

$model = BertForSequenceClassification::fromPretrained('Xenova/toxic-bert');
```

If you know the specific model class you want to use, it's recommended to use it directly. This is because the model
classes are more specialized and provide more specific methods and properties that can be useful for the task you're
performing. However, if you're not sure which model class to use, the auto model classes are a good starting point.







