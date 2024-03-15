---
outline: deep
---

# Question Answering

The Question Answering (QA) pipeline enables models to extract or generate answers to questions based on a given text
context. This functionality is particularly useful for digging through documents to find answers or even generating
answers without direct context in some advanced models.

## Task ID

- `question-answering`

## Default Model

- `Xenova/distilbert-base-uncased-distilled-squad`

## Use Cases

Question Answering models are versatile tools with numerous applications, such as:

- **FAQ Handling:** Utilize Question Answering models to dynamically answer frequently asked questions on websites or in
  applications. By feeding the model relevant documents or text snippets, you can create a responsive FAQ system that
  adapts to user queries with precise answers, improving the efficiency of helpdesks and information portals.
- **Content Discovery:** Improve search functionalities within websites, apps, or databases by directly providing
  answers extracted from content, enhancing user experience and content accessibility.
- **Compliance and Legal Aid:** QA models can help professionals quickly locate specific information within extensive
  legal documents or compliance guidelines, simplifying legal research and compliance checks.
- **Research and Study:** Students and researchers can efficiently find specific answers within large documents or study
  materials, saving time and enhancing productivity.
- **Customer Support Automation:** By automating responses to frequently asked questions using a knowledge base as
  context, businesses can streamline their customer support, providing quick and accurate answers to customer inquiries.

## Running a Pipeline Session

To use the Question Answering pipeline, you'll need to provide both a question and a context. The context is the text or
document where the model will look for the answer. Here's an example:

```php
$question = "Who is known as the father of computers?";

$context = "The history of computing is longer than the history of computing hardware and modern computing technology 
and includes the history of methods intended for pen and paper or for chalk and slate, with or without the aid of tables. 
Charles Babbage is often regarded as one of the fathers of computing because of his contributions to the basic design of 
the computer through his analytical engine.";

$pipeline = pipeline('question-answering', 'Xenova/distilbert-base-cased-distilled-squad');

$result = $pipeline($question, $context);

```

## Pipeline Input Options

When running the `question-answering` pipeline, you can the following arguments:

-  ### `question` *(string)*

The question you'd like to ask. It's the first argument so there's no need to include it as a named argument.

- ### `context` *(string)*
  This provides the text that contains the potential answer. It's always going to be the second argument so there's no
  need to include it as a named argument.

- ### `topK`
  [Optional] Specifies how many potential answers to return. By default, it's set to 1, meaning the model will return
  the highest-scoring answer. Increasing this value allows you to see more possible answers along with their confidence
  scores.
  
  ```php
  $result = $pipeline($question, $context, topK: 2);
  ```

## Pipeline Outputs

The output of the pipeline is typically an array containing the best answer found within the context, along with its
confidence score. . When `topK` is set to a value greater than 1, the output will include multiple answers, sorted by
their confidence scores.

E.g. For a single best answer (`topK` = 1), the output might look like this:

```php
["answer" => "Charles Babbage", "score" =>  0.99892232198209]
```

And for `topK` = 2

```php
[
  ["answer" => "Charles Babbage", "score" =>  0.99892232198209],
  ["answer" => "Charles", "score" =>  0.00048067486262722]
]
```