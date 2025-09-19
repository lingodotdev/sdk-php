
LingoDotDevEngine wraps the Lingo.dev localization API for PHP consumers.

Use a single engine instance to translate strings, arrays, and chat logs, or
to detect the locale of free-form text. The engine handles request batching,
progress reporting, and surfacing validation or transport errors.

Example (basic setup):
    $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);

Example (Laravel integration):
    $engine = new LingoDotDevEngine(['apiKey' => config('services.lingodotdev.api_key')]);
    $engine->localizeText($request->message, ['sourceLocale' => 'en', 'targetLocale' => 'es']);

***

* Full name: `\LingoDotDev\Sdk\LingoDotDevEngine`

**See Also:**

* https://lingo.dev

## Methods

### __construct

Build an engine with your API key and optional batching limits.

```php
public __construct(array{apiKey: string, apiUrl?: string, batchSize?: int, idealBatchItemSize?: int} $config = []): mixed
```

Example:
$engine = new LingoDotDevEngine([
    'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
    'batchSize' => 100,
    'idealBatchItemSize' => 1000,
]);

**Parameters:**

| Parameter | Type                                                                                  | Description           |
|-----------|---------------------------------------------------------------------------------------|-----------------------|
| `$config` | **array{apiKey: string, apiUrl?: string, batchSize?: int, idealBatchItemSize?: int}** | Configuration options |

**Throws:**

API key missing or values invalid
- [`InvalidArgumentException`](../../InvalidArgumentException)

***

### localizeObject

Localize every string in a nested array while keeping its shape intact.

```php
public localizeObject(array<string,mixed> $obj, array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string,mixed>|null} $params, callable|null $progressCallback = null): array<string,mixed>
```

Example:
$content = ['greeting' => 'Hello'];
$engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
$engine->localizeObject($content, ['sourceLocale' => 'en', 'targetLocale' => 'fr']);

**Parameters:**

| Parameter           | Type                                                                                                             | Description                          |
|---------------------|------------------------------------------------------------------------------------------------------------------|--------------------------------------|
| `$obj`              | **array<string,mixed>**                                                                                          | Nested data structure to translate   |
| `$params`           | **array{targetLocale: string, sourceLocale?: string\|null, fast?: bool, reference?: array<string,mixed>\|null}** | Translation options                  |
| `$progressCallback` | **callable\|null**                                                                                               | Progress callback (%, batch, result) |

**Return Value:**

Translated data preserving structure

**Throws:**

Invalid parameters or reference
- [`InvalidArgumentException`](../../InvalidArgumentException)
API request failure
- [`RuntimeException`](../../RuntimeException)

***

### localizeText

Localize a single string and return the translated text.

```php
public localizeText(string $text, array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string,mixed>|null} $params, callable|null $progressCallback = null): string
```

Examples:
$engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
$engine->localizeText('Hello, world!', ['sourceLocale' => 'en', 'targetLocale' => 'es']);

$engine->localizeText(
    'This is a very long text that needs translation...',
    ['sourceLocale' => 'en', 'targetLocale' => 'es'],
    function (int $progress): void {
        echo 'Translation progress: ' . $progress . "%%\n";
    }
);

$engine->localizeText('Bonjour le monde', ['sourceLocale' => null, 'targetLocale' => 'en']);

**Parameters:**

| Parameter           | Type                                                                                                             | Description                |
|---------------------|------------------------------------------------------------------------------------------------------------------|----------------------------|
| `$text`             | **string**                                                                                                       | Text to translate          |
| `$params`           | **array{targetLocale: string, sourceLocale?: string\|null, fast?: bool, reference?: array<string,mixed>\|null}** | Translation options        |
| `$progressCallback` | **callable\|null**                                                                                               | Progress callback (0-100%) |

**Return Value:**

Translated text or empty string

**Throws:**

Missing or invalid parameters
- [`InvalidArgumentException`](../../InvalidArgumentException)
API request failure
- [`RuntimeException`](../../RuntimeException)

***

### batchLocalizeText

Localize a string into multiple languages and return texts in order.

```php
public batchLocalizeText(string $text, array{sourceLocale: string, targetLocales: string[], fast?: bool} $params): string[]
```

Example:
$engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
$engine->batchLocalizeText('Hello, world!', [
    'sourceLocale' => 'en',
    'targetLocales' => ['es', 'fr', 'de'],
]);

**Parameters:**

| Parameter | Type                                                                  | Description               |
|-----------|-----------------------------------------------------------------------|---------------------------|
| `$text`   | **string**                                                            | Text to translate         |
| `$params` | **array{sourceLocale: string, targetLocales: string[], fast?: bool}** | Batch translation options |

**Return Value:**

Translated texts in targetLocales order

**Throws:**

Missing or invalid parameters
- [`InvalidArgumentException`](../../InvalidArgumentException)
Individual request failure
- [`RuntimeException`](../../RuntimeException)

***

### localizeChat

Localize a chat transcript while preserving speaker names.

```php
public localizeChat(array<int,array{name: string, text: string}> $chat, array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string,mixed>|null} $params, callable|null $progressCallback = null): array<int,array{name: string, text: string}>
```

Example:
$conversation = [
    ['name' => 'Alice', 'text' => 'Hello, how are you?'],
    ['name' => 'Bob', 'text' => 'I am fine, thank you!'],
];
$engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
$engine->localizeChat($conversation, ['sourceLocale' => 'en', 'targetLocale' => 'de']);

**Parameters:**

| Parameter           | Type                                                                                                             | Description                          |
|---------------------|------------------------------------------------------------------------------------------------------------------|--------------------------------------|
| `$chat`             | **array<int,array{name: string, text: string}>**                                                                 | Conversation with names and messages |
| `$params`           | **array{targetLocale: string, sourceLocale?: string\|null, fast?: bool, reference?: array<string,mixed>\|null}** | Translation options                  |
| `$progressCallback` | **callable\|null**                                                                                               | Progress callback (0-100%)           |

**Return Value:**

Translated chat preserving names

**Throws:**

Invalid chat entries or parameters
- [`InvalidArgumentException`](../../InvalidArgumentException)
API request failure
- [`RuntimeException`](../../RuntimeException)

***

### recognizeLocale

Identify the locale of the provided text.

```php
public recognizeLocale(string $text): string
```

Example:
$engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
$engine->recognizeLocale('Bonjour le monde');

**Parameters:**

| Parameter | Type       | Description                        |
|-----------|------------|------------------------------------|
| `$text`   | **string** | Sample text for language detection |

**Return Value:**

ISO language code (e.g., 'en', 'es', 'zh')

**Throws:**

Empty text provided
- [`InvalidArgumentException`](../../InvalidArgumentException)
Invalid API response or request failure
- [`RuntimeException`](../../RuntimeException)

***
