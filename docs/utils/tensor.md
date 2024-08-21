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

## Tensors in TransformersPHP

The `Tensor` class in TransformersPHP provides a flexible and efficient way to work with tensors in PHP. By default, it
uses a C-based buffer to store the tensor's data, which allows for fast element-wise operations and mathematical
operations using OpenBLAS. The operations can further be accelerated if you installed OpenMP - allowing for parallel
computation across multiple cores. TransformersPHP selects the best available backend for your system, depending on the
installed libraries. OpenBLAS is already included in the package, so you don't need to install it separately. However,
you can install OpenMP to enable parallel computation. Checkout the OpenMP installation guide for your operating system.

There are few edge cases where OpenBLAS might not be installed properly. In such cases, TransformersPHP will fall back
to using the PHP-based buffer, which is slower but still functional.

## Creating a Tensor

You can create a tensor using the Tensor class constructor or by converting from a multidimensional array using the
fromArray method. Below are examples of how to create tensors:

### Using the Constructor

```php
use Codewithkyrian\Transformers\Tensor\Tensor;

$data = [1, 2, 3, 4, 5, 6];
$shape = [2, 3];
$dtype = Tensor::int16;
$tensor = new Tensor($data, $dtype, $shape); // If dtype is not provided, it defaults to Tensor::float32
```

### Using the `fromArray` Method

Create a tensor from a provided array. The array can be either flat or nested, and the shape of the tensor is inferred
from the array's structure. Ensure the array's shape is consistent, as an exception will be thrown if it is not.

```php
use Codewithkyrian\Transformers\Tensor\Tensor;

$data = [[1, 2, 3], [4, 5, 6]];
$tensor = Tensor::fromArray($data);
// $tensor: [[1, 2, 3], [4, 5, 6]]
```

### Using the `fill` Method

Create a tensor with a specified shape, filled with a given value. This is useful for initializing tensors with a
default value.

```php
use Codewithkyrian\Transformers\Tensor\Tensor;

$shape = [2, 3];
$value = 5;
$tensor = Tensor::fill($shape, $value); //  [[5, 5, 5], [5, 5, 5]]
```

### Using the `zeros` and `ones` Methods

Create tensors filled with zeros or ones, respectively. The shape of the tensor is determined by the provided
dimensions.

```php
use Codewithkyrian\Transformers\Tensor\Tensor;

$shape = [2, 3];
$zerosTensor = Tensor::zeros($shape); // [[0, 0, 0], [0, 0, 0]]
$onesTensor = Tensor::ones($shape); //  [[1, 1, 1], [1, 1, 1]]
```

### Using the `zerosLike` and `onesLike` Methods

Create tensors of zeros or ones with the same shape as an existing tensor. This is helpful when you need tensors with
the same dimensions as a reference tensor.

```php
use Codewithkyrian\Transformers\Tensor\Tensor;

$data = [[1, 2, 3], [4, 5, 6]];
$tensor = Tensor::fromArray($data);

$zeros = Tensor::zerosLike($tensor); // [[0, 0, 0], [0, 0, 0]]
$ones = Tensor::onesLike($tensor); // [[1, 1, 1], [1, 1, 1]]
```

### Using the `repeat` Method

Repeat a tensor along a specified axis or dimensions. The `repeats` parameter indicates how many times to repeat the
tensor along the given axis. If no axis is provided, the tensor is repeated across all dimensions.

```php
use Codewithkyrian\Transformers\Tensor\Tensor;

$data = [[1, 2], [3, 4]];
$tensor = Tensor::fromArray($data);

$repeatedTensor = Tensor::repeat($tensor, 2, 0); // Repeat tensor along axis 0 (rows)
// $repeatedTensor : [
// [ [1, 2], [3, 4] ],
// [ [1, 2], [3, 4] ]
//]
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
- ### `toArray()`
  Returns the tensor's data as a multidimensional array.
- ### `toBufferArray()`
  Returns the tensor's flat buffer as a regular PHP array.
- ### `toString()`
  Returns the tensor’s data as a raw binary string.

```php
use Codewithkyrian\Transformers\Tensor\Tensor;

$data = [[1, 2, 3], [4, 5, 6]];

$tensor = Tensor::fromArray($data);

$shape = $tensor->shape(); // [2, 3]
$dtype = $tensor->dtype(); // NDArray::float32
$size = $tensor->size(); // 6
$count = $tensor->count(); // 2
$ndim = $tensor->ndim(); // 2
$array = $tensor->toArray(); // [[1, 2, 3], [4, 5, 6]]
$bufferArray = $tensor->toBufferArray(); // [1, 2, 3, 4, 5, 6]
$binString = $tensor->toString(); // b"\x00\x00€?\x00\x00\x00@\x00\x00@@\x00\x00€@\x00\x00 @\x00\x00À@"
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
  
- ### `sliceWithBounds(array $start, array $size)`
  Slices the tensor with the given bounds.
  
  Parameters:
  - `$start`: The starting indices of the slice.
  - `$size`: The size of the slice.
  
  Returns:
  - A new tensor with the specified slice.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([[1, 2, 3], [4, 5, 6], [7, 8, 9]]);
  
  $slicedTensor = $tensor->sliceWithBounds([0, 1], [2, 2]); 
  // [[2, 3],
  //  [5, 6]]
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

- ### `topk(int $k = null, bool $sorted = true)`
  Returns the top k elements and their indices along the specified axis.

  Parameters:
    - `$k`: The number of top elements to return. If not provided, all elements are returned.
    - `$sorted`: Whether to return the elements in sorted order.

  Returns:
    - A tuple containing two tensors: the top k elements and their indices.

  Example:
  ```php
  $data = [[1, 2, 3], [4, 5, 6]];
  $tensor = Tensor::fromArray($data);
  
  [$values, $indices] = $tensor->topk(2);
  
  $values->toArray(); // [[3, 2], [6, 5]]
  $indices->toArray(); // [[2, 1], [2, 1]]
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

- ### `multiply(Tensor|float|int $value)`
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

- ### `sum(?int $axis = null)`

  Calculates the sum of the tensor's elements, optionally along a specific axis.

  Parameters:
  - `$axis` (optional): The axis along which to calculate the sum. If not provided, the sum of all elements is returned.

  Returns:
  - A scalar value representing the sum if no axis is provided, or a tensor with the sums along the specified axis.

  Example:
  ```php
  $tensor = Tensor::fromArray([[1, 2, 3], [4, 5, 6]]);
  
  $sumTensor = $tensor->sum(); // 21
  $sumTensorAxis0 = $tensor->sum(0); // [5, 7, 9]
  ```

- ### `pow(float|Tensor $exponent)`

  Raises the tensor to the power of a scalar or element-wise power of another tensor.

  Parameters:
  - `$exponent`: The exponent to which to raise the tensor. This can be a scalar or another tensor.

  Returns:
  - A new tensor with each element raised to the specified power.

  Example:
  ```php
  $tensor = Tensor::fromArray([1, 2, 3]);
  
  $powTensor = $tensor->pow(2); // [1, 4, 9]
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

- ### `stack(array $tensors, int $axis = 0)`

  Stacks an array of tensors along a specified axis.
  
  Parameters:
  - `$tensors`: The array of tensors to stack.
  - `$axis`: The axis to stack along. Default is `0`.
  
  Returns:
  - A new tensor that is the result of stacking the input tensors along the specified axis.
  
  Example:
  ```php
  $tensor1 = Tensor::fromArray([1, 2]);
  $tensor2 = Tensor::fromArray([3, 4]);
  
  $stacked = Tensor::stack([$tensor1, $tensor2], 0); // [[1, 2], [3, 4]]
  ```

- ### `concat(array $tensors, int $axis = 0)`
  
  Concatenates an array of tensors along a specified dimension.
  
  Parameters:
  - `$tensors`: The array of tensors to concatenate.
  - `$axis`: The dimension to concatenate along. Default is `0`.
  
  Returns:
  - A new tensor that is the result of concatenating the input tensors along the specified dimension.
  
  Example:
  ```php
  $tensor1 = Tensor::fromArray([1, 2]);
  $tensor2 = Tensor::fromArray([3, 4]);
  
  $concatenated = Tensor::concat([$tensor1, $tensor2], 0); // [1, 2, 3, 4]
  ```

- ### `log()`

  Applies the natural logarithm to each element in the tensor.
  
  Returns:
  - A new tensor with the logarithm of each element.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([1, 2, 3]);
  
  $logTensor = $tensor->log(); // [0, 0.6931, 1.0986]
  ```

- ### `exp()`

  Applies the exponential function to each element in the tensor.
  
  Returns:
  - A new tensor with the exponential of each element.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([1, 2, 3]);
  
  $expTensor = $tensor->exp(); // [2.7183, 7.3891, 20.0855]
  ```

- ### `reciprocal()`

  Calculates the reciprocal (1/x) of each element in the tensor.
  
  Returns:
  - A new tensor with the reciprocal of each element.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([1, 2, 4]);
  
  $reciprocalTensor = $tensor->reciprocal(); // [1, 0.5, 0.25]
  ```

- ### `round(int $precision = 0)`

  Rounds each element in the tensor to the specified number of decimal places.
  
  Parameters:
  - `$precision`: The number of decimal places to round to. Default is `0`.
  
  Returns:
  - A new tensor with the elements rounded to the specified precision.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([1.123, 2.567, 3.891]);
  
  $roundedTensor = $tensor->round(1); // [1.1, 2.6, 3.9]
  ```

- ### `to(int $dtype)`

  Casts the tensor to a specified data type.
  
  Parameters:
  - `$dtype`: The data type to cast the tensor to.
  
  Returns:
  - A new tensor with the specified data type.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([1.1, 2.2, 3.3]);
  
  $intTensor = $tensor->to(Tensor::int32); // [1, 2, 3]
  ```

- ### `mean(?int $axis = null, bool $keepShape = false)`

  Returns the mean value of the tensor elements along a specified axis.
  
  Parameters:
  - `$axis` (optional): The axis along which to calculate the mean. If not provided, the mean of all elements is returned.
  - `$keepShape` (optional): Whether to keep the reduced dimension or not. Default is `false`.
  
  Returns:
  - A scalar value representing the mean if no axis is provided, or a tensor with the means along the specified axis.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([[1, 2, 3], [4, 5, 6]]);
  
  $meanTensor = $tensor->mean(); // 3.5
  $meanTensorAxis0 = $tensor->mean(0); // [2.5, 3.5, 4.5]
  ```

- ### `stdMean(?int $axis = null, int $correction = 1, bool $keepShape = false)`

  Calculates the standard deviation and mean of the tensor elements along a specified axis.
  
  Parameters:
  - `$axis` (optional): The axis along which to calculate the standard deviation and mean. If not provided, the calculation is done over all elements.
  - `$correction`: The type of normalization. Default is `0`.
  - `$keepShape` (optional): Whether to keep the reduced dimension or not. Default is `false`.
  
  Returns:
  - An array with the standard deviation and mean of the tensor.
  
  Example:
  ```php
  $tensor = Tensor::fromArray([[1, 2, 3], [4, 5, 6]]);
  
  [$std, $mean] = $tensor->stdMean(0); 
  // $std = [1.5, 1.5, 1.5]
  // $mean = [2.5, 3.5, 4.5]
  ```
  



  

  

  




