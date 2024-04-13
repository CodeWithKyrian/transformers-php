---
outline: deep
---

# Image Feature Extraction <Badge type="tip" text="^0.3.0" />

Image feature extraction is a computer vision task that involves extracting high-level features from images. These
features can be used for various purposes, such as image similarity search, image retrieval, and content-based image
retrieval. The task accepts image inputs and returns a feature vector that represents the image.

## Task ID

- `image-feature-extraction`

## Default Model

- `Xenova/vit-base-patch16-224-in21k`

## Use Cases

Image feature extraction models find application in various scenarios, including:

- **Image Retrieval:** Generating feature vectors for images to enable similarity search and retrieval of similar images
  from a database.
- **Content-Based Image Retrieval:** Enabling search engines to retrieve images based on their visual content rather
  than textual metadata.
- **Image Similarity Search:** Finding visually similar images based on their feature representations.
- **Visual Search:** Enhancing e-commerce platforms by allowing users to search for products using images rather than
  text.

## Running an Inference Session

Here's how to perform image feature extraction using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$extractor = pipeline('image-feature-extraction');

$result = $extractor('path/to/image.jpg');
```

## Pipeline Input Options

When running the `image-feature-extraction` pipeline, you can use the following options:

- ### `texts` *(string|array)*
  The image(s) from which features are extracted. You can pass a single image path or an array of image paths for batch
  processing. It's required and is the first argument, so there's no need to pass it as a named argument.

- ### `pool` *(bool)*
  When set to `true`, it averages the feature vectors across all patches in the image. Before using this option, make
  sure the model has a pooler layer. The default value is `false`.

## Pipeline Output

The output of the `image-feature-extraction` pipeline is a feature vector that represents the input image. The shape
and size of the feature vector depend on the model architecture and configuration. For no pooling, the shape is
usually `[X, Y, Z]` where :

- `X` Represents the batch size (1 for single image input).
- `Y` Denotes the sequence length or dimensionality of the features extracted from each token or patch. This dimension
  is typically fixed across tokens and corresponds to the size of the feature vectors extracted from the image patches.
- `Z` Represents the size of the feature vector extracted from each patch. This dimension is typically fixed across
  patches and corresponds to the size of the feature vectors extracted from the image patches.

For example, with certain models, such as those based on the Vision Transformer (ViT) architecture, the feature vector's
shape might be `[1, 197, 768]`.

When pooling is applied, the output shape is typically `[X, Z]`, where `Z` represents the size of the pooled feature
vector.
Pooling aggregates information from all the tokens or patches into a single feature vector, resulting in a
reduced-dimensional representation of the input image. eg `[1, 768]`.

