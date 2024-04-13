---
outline: deep
---

# Tensor

In machine learning and numerical computing, a **tensor** is a fundamental data structure used to represent
multidimensional arrays. A tensor can be a scalar (0-dimensional tensor), a vector (1-dimensional tensor), a matrix
(2-dimensional tensor), or a higher-dimensional array. Tensors are the primary data structure used in deep learning
frameworks to represent input data, model parameters, and output data.

## Why Tensors?

Tensors are preferred over regular arrays for several reasons:

- **Efficient Memory Management:** Tensors maintain a flat buffer of data along with shape information, allowing for
  efficient
  memory management and faster access to elements compared to nested arrays.
- **Support for High-dimensional Data:** Machine learning often involves working with high-dimensional data, where
  tensors provide a convenient and scalable way to represent and manipulate such data structures.
- **Optimized Operations:** Tensors enable optimized element-wise mathematical and matrix operations, making them
  suitable for various mathematical computations required in machine learning algorithms.

## Properties of Tensors

Tensors have the following properties:

- **Rank:** The rank of a tensor refers to the number of dimensions it has. Scalars have a rank of 0, vectors have a
  rank of 1, matrices have a rank of 2, and so on.
- **Shape:** The shape of a tensor specifies the number of elements along each dimension. For example, a 3x4 matrix
  has a shape of `(3, 4)`.
- **Data Type:** Tensors can store data of different types, such as integers, floating-point numbers, or strings. The
  data type of a tensor determines the type of values it can store.
- **Size:** The size of a tensor is the total number of elements it contains. It is calculated as the product of the
  elements in the shape tuple.
- **Buffer:** The buffer of a tensor is a contiguous block of memory that stores the tensor's data. It is a
  one-dimensional array that holds the tensor's elements in a linear order.
- **Strides:** The strides of a tensor define the number of bytes to skip in memory to move to the next element along
  each dimension. Strides are used to efficiently access elements in a tensor without the need for reshaping the
  underlying data.

## Creating a Tensor

You can create a tensor using the Tensor class constructor or by converting from a multidimensional array using the
fromArray method. Below are examples of how to create tensors:

### Using the Constructor

```php
use Codewithkyrian\Transformers\Utils\Tensor;
use Interop\Polite\Math\Matrix\NDArray;

$data = [1, 2, 3, 4, 5, 6];
$shape = [2, 3];
$dtype = NDArray::int16;
$tensor = new Tensor($data, $dtype, $shape); // If dtype is not provided, it defaults to NDArray::float32
```

### Using the fromArray Method

```php
use Codewithkyrian\Transformers\Utils\Tensor;

$data = [[1, 2, 3], [4, 5, 6]];
$tensor = Tensor::fromArray($data);
```

### Using zeros and ones methods

```php
use Codewithkyrian\Transformers\Utils\Tensor;

$shape = [2, 3];
$tensor = Tensor::zeros($shape); // Creates a tensor of zeros with the specified shape
$tensor = Tensor::ones($shape); // Creates a tensor of ones with the specified shape
```

### Using zerosLike and onesLike methods

```php
use Codewithkyrian\Transformers\Utils\Tensor;

$data = [[1, 2, 3], [4, 5, 6]];
$tensor = Tensor::fromArray($data);

$zeros = Tensor::zerosLike($tensor); // Creates a tensor of zeros with the same shape as the input tensor
$ones = Tensor::onesLike($tensor); // Creates a tensor of ones with the same shape as the input tensor
```

## Accessing Tensor Properties

The `Tensor` class provides methods to access various properties of a tensor, such as its shape, data type, size, count
and buffer.

- ### `shape()`
  Returns the shape of the tensor as a tuple of integers.
- ### `dtype()`
  Returns the data type of the tensor.
- ### `size()`
  Returns the total number of elements in the tensor.
- ### `count()`
  Returns the number of elements in the tensor. This is different from the size, which is the total number of elements.
- ### `stride()`
  Returns the strides of the tensor as a tuple of integers.
- ### `ndim()`
  Returns the number of dimensions of the tensor.
- ### `buffer()`
  Returns the buffer containing the tensor's data as a one-dimensional array buffer. This is not a regular PHP array,
  but it implements the `ArrayAccess`, `Countable`, and `Iterator` interfaces so you can loop over it or access elements
  by index.
- ### `toArray()`
  Returns the tensor's data as a multidimensional array.
- ### `toBufferArray()`
  Returns the tensor's flat buffer as a regular PHP array.

```php
use Codewithkyrian\Transformers\Utils\Tensor;

$data = [[1, 2, 3], [4, 5, 6]];

$tensor = Tensor::fromArray($data);

$shape = $tensor->shape(); // [2, 3]
$dtype = $tensor->dtype(); // NDArray::float32
$size = $tensor->size(); // 6
$count = $tensor->count(); // 2
$ndim = $tensor->ndim(); // 2
$buffer = $tensor->buffer(); // SplFixedArray {0: 1, 1: 2, 2: 3, 3: 4, 4: 5, 5: 6}
$array = $tensor->toArray(); // [[1, 2, 3], [4, 5, 6]]
$bufferArray = $tensor->toBufferArray(); // [1, 2, 3, 4, 5, 6]
```

## Tensor Operations

The `Tensor` class provides methods for performing various operations on tensors, such as element-wise operations,
matrix multiplication, reshaping, transposing, etc. Below are some common tensor operations:

- ### `squeeze(?int $dim = null)`
  Removes dimensions of size 1 from the tensor.

  Parameters:
    - `$dim` (optional): The dimension to squeeze. If not provided, all dimensions of size 1 will be removed.

  Returns:
    - A new tensor with the specified dimensions squeezed.

  Example:
  ```php
  $data = [[1], [2], [3]];
  $tensor = Tensor::fromArray($data);
  
  $squeezed = $tensor->squeeze(); // [[1, 2, 3]]
  ```

- ### `unsqueeze(int $dim)`
  Adds a dimension of size 1 at the specified position in the tensor.

  Parameters:
    - `$dim`: The position at which to add the new dimension.

  Returns:
    - A new tensor with the additional dimension.

  Example:
  ```php
  $data = [1, 2, 3];
  $tensor = Tensor::fromArray($data);
  
  $unsqueezed = $tensor->unsqueeze(0); // [[1, 2, 3]]
  ```

- ### `reshape(array $shape)`
  Reshapes the tensor to the specified shape.

  Parameters:
    - `$shape`: The new shape of the tensor.

  Returns:
    - A new tensor with the reshaped dimensions.

  Example:
  ```php
  $data = [1, 2, 3, 4, 5, 6];
  $tensor = Tensor::fromArray($data);
  
  $reshaped = $tensor->reshape([2, 3]);
  
  $reshaped->toArray(); // [[1, 2, 3], [4, 5, 6]]
  ```

- ### `transpose()`
  Transposes the tensor by reversing the dimensions.

  Returns:
    - A new tensor with the dimensions reversed.

  Example:
  ```php
  $data = [[1, 2], [3, 4], [5, 6]];
  $tensor = Tensor::fromArray($data);
  
  $transposed = $tensor->transpose();
  
  $transposed->toArray(); // [[1, 3, 5], [2, 4, 6]]
  ```

- ### `permute(...$axes)`
  Permutes the dimensions of the tensor according to the specified axes.

  Parameters:
    - `$axes`: The new order of dimensions.

  Returns:
    - A new tensor with the permuted dimensions.

  Example:
  ```php
  $data = [[[1, 2], [3, 4]], [[5, 6], [7, 8]]];
  $tensor = Tensor::fromArray($data);
  
  $permuted = $tensor->permute(1, 0, 2);
  
  $permuted->toArray(); // [[[1, 2], [5, 6]], [[3, 4], [7, 8]]]
  ```

- ### `clamp(float|int $min, float|int $max)`
  Clamps all elements in the tensor to be within the specified range.

  Parameters:
    - `$min`: The minimum value to clamp the elements to.
    - `$max`: The maximum value to clamp the elements to.

  Returns:
    - A new tensor with the elements clamped to the specified range.

  Example:
    ```php
    $data = [[-1, 0, 1], [2, 3, 4]];
    $tensor = Tensor::fromArray($data);
    
    $clamped = $tensor->clamp(0, 2);
    
    $clamped->toArray(); // [[0, 0, 1], [2, 2, 2]]
    ```

- ### `slice(...$slices)`
  Slices the tensor along the specified dimensions.

  Parameters:
    - `$slices`: The slices to apply along each dimension. Each slice can be an integer, a range, or null.

  Returns:
    - A new tensor containing the sliced elements.

  Example:
  ```php
  $data = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
  $tensor = Tensor::fromArray($data);
  
  $sliced = $tensor->slice(1, 2);
  
  $sliced->toArray(); // [[5]]
  ```

- ### `softmax()`
  Computes the softmax function across the tensor. The softmax function is used to normalize the input values into a
  probability distribution. This method only works for 1-dimensional and 2-dimensional tensors.

  Parameters:
    - `$axis`: The axis along which to compute the softmax. The default is the last axis.

  Returns:
    - A new tensor containing the softmax values.

  Example:
  ```php
  $data = [[1, 2, 3], [4, 5, 6]];
  $tensor = Tensor::fromArray($data);
  
  $softmax = $tensor->softmax();
  
  $softmax->toArray(); // [[0.09003057317038, 0.2447284710548, 0.66524095577482], [0.09003057317038, 0.2447284710548, 0.66524095577482]]
  ```

- ### `max(int $axis = null)`
  Returns the maximum values along the specified axis.

  Parameters:
    - `$axis`: The axis along which to find the maximum values. If not provided, the flattened tensor is used.

  Returns:
    - A single value representing the maximum value, or a tensor of maximum values if an axis is specified.

  Example:
  ```php
  $data = [[1, 2, 3], [4, 5, 6]];
  $tensor = Tensor::fromArray($data);
  
  $max = $tensor->max(); // 6
  
  $max = $tensor->max(1); // [3, 6]
  ```

- ### `argMax(int $axis = null)`
  Returns the indices of the maximum values along the specified axis.

  Parameters:
    - `$axis`: The axis along which to find the maximum values. If not provided, the flattened tensor is used.

  Returns:
    - An integer representing the index of the maximum value, or a tensor of indices if an axis is specified.

  Example:
  ```php
  $data = [[1, 2, 3], [4, 5, 6]];
  $tensor = Tensor::fromArray($data);
  
  $argmax = $tensor->argMax(); // 5
  
  $argmax = $tensor->argMax(1); // [2, 2]
  ```

- ### `min(int $axis = null)`
  Returns the minimum values along the specified axis.

  Parameters:
    - `$axis`: The axis along which to find the minimum values. If not provided, the flattened tensor is used.

  Returns:
    - A single value representing the minimum value, or a tensor of minimum values if an axis is specified.

  Example:
  ```php
  $data = [[1, 2, 3], [4, 5, 6]];
  $tensor = Tensor::fromArray($data);
  
  $min = $tensor->min(); // 1
  
  $min = $tensor->min(1); // [1, 4]
  ```

- ### `argMin(int $axis = null)`
  Returns the indices of the minimum values along the specified axis.

  Parameters:
    - `$axis`: The axis along which to find the minimum values. If not provided, the flattened tensor is used.

  Returns:
    - An integer representing the index of the minimum value, or a tensor of indices if an axis is specified.

  Example:
    ```php
    $data = [[1, 2, 3], [4, 5, 6]];
    $tensor = Tensor::fromArray($data);
    
    $argmin = $tensor->argMin(); // 0
    
    $argmin = $tensor->argMin(1); // [0, 0]
    ```

- ### `mean(int $axis = null, bool $keepdims = false)`
  Computes the mean values along the specified axis.

  Parameters:
    - `$axis`: The axis along which to compute the mean values. If not provided, the flattened tensor is used.
    - `$keepdims`: Whether to keep the dimensions of the input tensor in the output tensor.

  Returns:
    - A single value representing the mean value, or a tensor of mean values if an axis is specified.

  Example:
    ```php
    $data = [[1, 2, 3], [4, 5, 6]];
    $tensor = Tensor::fromArray($data);
    
    $mean = $tensor->mean(); // 3.5
  
    $mean = $tensor->mean(1); // [2, 5]
    ```

- ### `meanPooling(Tensor $other)`
  Computes the mean pooling operation between two tensors. The mean pooling operation calculates the average of the
  corresponding elements in the two tensors.

  Parameters:
    - `$other`: The tensor to perform the mean pooling operation with.

  Returns:
    - A new tensor containing the mean pooled values.

  Example:
    ```php
    $data1 = [[[1, 2], [3, 4]], [[5, 6], [7, 8]], [[9, 0], [1, 2]]];
    $data2 = [[[1, 2], [3, 4]], [[5, 6], [7, 8]], [[9, 0], [1, 2]]];
    $tensor1 = Tensor::fromArray($data1);
    $tensor2 = Tensor::fromArray($data2);
    
    $meanPooled = $tensor1->meanPooling($tensor2);
    
    $meanPooled->toArray(); // [[2, 3], [6, 7], [5, 1]]
    ```

- ### `sigmoid()`
  Computes the sigmoid function element-wise on the tensor. The sigmoid function is a common activation function used in
  neural networks to introduce non-linearity.

  Returns:
    - A new tensor containing the sigmoid values.

  Example:
    ```php
    $data = [[-1, 0, 1], [2, 3, 4]];
    $tensor = Tensor::fromArray($data);
    
    $sigmoid = $tensor->sigmoid();
    
    $sigmoid->toArray(); // [[0.26894142136999, 0.5, 0.73105857863001], [0.88079707797788, 0.95257412682243, 0.98201379003791]]
    ```

- ### `add(Tensor|float|int $other)`
  Adds the specified tensor or scalar value to the current tensor.

  Parameters:
    - `$other`: The tensor or scalar value to add.

  Returns:
    - A new tensor containing the element-wise sum.

  Example:
    ```php
    $data = [[1, 2], [3, 4]];
    $tensor = Tensor::fromArray($data);
  
    $result = $tensor->add(5); // [[6, 7], [8, 9]]
  
    $tensor2 = Tensor::fromArray([[5, 6], [7, 8]]);
  
    $result = $tensor->add($tensor2); // [[6, 8], [10, 12]]
    ```

- ### `multiply(float|int $value)`
  Multiplies the tensor by the specified scalar value.

  Parameters:
    - `$value`: The scalar value to multiply the tensor by.

  Returns:
    - A new tensor containing the element-wise product.

  Example:
    ```php
    $data = [[1, 2], [3, 4]];
    $tensor = Tensor::fromArray($data);
  
    $result = $tensor->multiply(2); // [[2, 4], [6, 8]]
    ```

- ### `dot(Tensor $other)`
  Computes the dot product between two tensors. The dot product is the sum of the element-wise product of the two
  tensors.

  Parameters:
    - `$other`: The tensor to compute the dot product with.

  Returns:
    - A new tensor containing the dot product.

  Example:
    ```php
    $data1 = [[1, 2], [3, 4]];
    $data2 = [[5, 6], [7, 8]];
    $tensor1 = Tensor::fromArray($data1);
    $tensor2 = Tensor::fromArray($data2);

    $result = $tensor1->dot($tensor2); // 70
    ```

- ### `cross(Tensor $other)`
  Computes the cross product between two tensors. The cross product is a vector perpendicular to the two input vectors.

  Parameters:
    - `$other`: The tensor to compute the cross product with.

  Returns:
    - A new tensor containing the cross product.

  Example:
    ```php
    $data1 = [[1, 0, 0], [0, 1, 0], [0, 0, 1]];
    $data2 = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
    $tensor1 = Tensor::fromArray($data1);
    $tensor2 = Tensor::fromArray($data2);

    $result = $tensor1->cross($tensor2); // [[-2, 4, -2], [4, -8, 4], [-2, 4, -2]]
    ```

- ### `norm(int $ord = 2, ?int $axis = null, bool $keepdims = false)`
  Computes the norm of the tensor along the specified axis.

  Parameters:
    - `$ord`: The order of the norm. Supported values are 1, 2, and INF.
    - `$axis`: The axis along which to compute the norm. If not provided, the flattened tensor is used.
    - `$keepdims`: Whether to keep the dimensions of the input tensor in the output tensor.

  Returns:
    - The norm of the tensor.

  Example:
    ```php
    $data = [[1, 2], [3, 4]];
    $tensor = Tensor::fromArray($data);

    $norm = $tensor->norm(); // 5.4772255750517

    $norm = $tensor->norm(1); // 10
    ```
  



  

  

  




