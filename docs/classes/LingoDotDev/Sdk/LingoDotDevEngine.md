
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

**Parameters:**

| Parameter | Type                                                                                  | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
|-----------|---------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$config` | **array{apiKey: string, apiUrl?: string, batchSize?: int, idealBatchItemSize?: int}** | Configuration options:
    - 'apiKey' (string, required): Your API token
    - 'apiUrl' (string): API base URL (default: https://engine.lingo.dev)
    - 'batchSize' (int): Records per request, 1-250 (default: 25)
    - 'idealBatchItemSize' (int): Max words per request, 1-2500 (default: 250)

Example:
    $engine = new LingoDotDevEngine([
        'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
        'batchSize' => 100,
        'idealBatchItemSize' => 1000,
    ]); |

**Throws:**

When API key is missing or values fail validation
- [`InvalidArgumentException`](../../InvalidArgumentException)

***

### localizeObject

Localize every string in a nested array while keeping its shape intact.

```php
public localizeObject(array<string,mixed> $obj, array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string,mixed>|null} $params, callable|null $progressCallback = null): array<string,mixed>
```

**Parameters:**

| Parameter           | Type                                                                                                             | Description                                                                                                                                                                                                                                                                                                                                                                                                         |
|---------------------|------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$obj`              | **array<string,mixed>**                                                                                          | Nested data structure containing text to translate                                                                                                                                                                                                                                                                                                                                                                  |
| `$params`           | **array{targetLocale: string, sourceLocale?: string\|null, fast?: bool, reference?: array<string,mixed>\|null}** | Translation options controlling locale, speed, and contextual reference data:
- 'targetLocale' (string, required): Language code to translate into (e.g., 'es', 'fr')
- 'sourceLocale' (string\|null): Language code of original text, null for auto-detection
- 'fast' (bool): Trade translation quality for speed
- 'reference' (array<string, mixed>\|null): Context data or glossary terms to guide translation |
| `$progressCallback` | **callable\|null**                                                                                               | Invoked per batch with (percentage complete, current batch, translated batch)                                                                                                                                                                                                                                                                                                                                       |

**Return Value:**

Translated data preserving original structure and non-text values

Example:
    $content = ['greeting' => 'Hello'];
    $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
    $engine->localizeObject($content, ['sourceLocale' => 'en', 'targetLocale' => 'fr']);

**Throws:**

When required params or reference data are invalid
- [`InvalidArgumentException`](../../InvalidArgumentException)
When API rejects or fails to process the request
- [`RuntimeException`](../../RuntimeException)

***

### localizeText

Localize a single string and return the translated text.

```php
public localizeText(string $text, array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string,mixed>|null} $params, callable|null $progressCallback = null): string
```

**Parameters:**

| Parameter           | Type                                                                                                             | Description                                                                                                                                                                                                                                                                                                                                                                                                                  |
|---------------------|------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$text`             | **string**                                                                                                       | Text content to translate                                                                                                                                                                                                                                                                                                                                                                                                    |
| `$params`           | **array{targetLocale: string, sourceLocale?: string\|null, fast?: bool, reference?: array<string,mixed>\|null}** | Translation options such as locale hints, speed preference, and contextual references:
- 'targetLocale' (string, required): Language code to translate into (e.g., 'es', 'fr')
- 'sourceLocale' (string\|null): Language code of original text, null for auto-detection
- 'fast' (bool): Trade translation quality for speed
- 'reference' (array<string, mixed>\|null): Context data or glossary terms to guide translation |
| `$progressCallback` | **callable\|null**                                                                                               | Called with completion percentage (0-100) during processing                                                                                                                                                                                                                                                                                                                                                                  |

**Return Value:**

Translated text, or empty string if translation unavailable

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

**Throws:**

When required params are missing or invalid
- [`InvalidArgumentException`](../../InvalidArgumentException)
When API rejects or fails to process the request
- [`RuntimeException`](../../RuntimeException)

***

### batchLocalizeText

Localize a string into multiple languages and return texts in order.

```php
public batchLocalizeText(string $text, array{sourceLocale: string, targetLocales: string[], fast?: bool} $params): string[]
```

**Parameters:**

| Parameter | Type                                                                  | Description                                                                                                                                                                                                                                                                                                    |
|-----------|-----------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$text`   | **string**                                                            | Text content to translate into multiple languages                                                                                                                                                                                                                                                              |
| `$params` | **array{sourceLocale: string, targetLocales: string[], fast?: bool}** | Batch translation options shared by all target locales:
- 'sourceLocale' (string, required): Language code of the original text (e.g., 'en')
- 'targetLocales' (string[], required): Array of language codes to translate into (e.g., ['es', 'fr', 'de'])
- 'fast' (bool): Trade translation quality for speed |

**Return Value:**

Array of translated texts in same order as targetLocales parameter

Example:
    $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
    $engine->batchLocalizeText('Hello, world!', [
        'sourceLocale' => 'en',
        'targetLocales' => ['es', 'fr', 'de'],
    ]);

**Throws:**

When required params are missing or invalid
- [`InvalidArgumentException`](../../InvalidArgumentException)
When an individual localization request fails
- [`RuntimeException`](../../RuntimeException)

***

### localizeChat

Localize a chat transcript while preserving speaker names.

```php
public localizeChat(array<int,array{name: string, text: string}> $chat, array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string,mixed>|null} $params, callable|null $progressCallback = null): array<int,array{name: string, text: string}>
```

**Parameters:**

| Parameter           | Type                                                                                                             | Description                                                                                                                                                                                                                                                                                                                                                                                                       |
|---------------------|------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$chat`             | **array<int,array{name: string, text: string}>**                                                                 | Conversation history with speaker names and their messages. Each entry must include:
- 'name' (string): Speaker label to preserve
- 'text' (string): Message content to translate                                                                                                                                                                                                                                 |
| `$params`           | **array{targetLocale: string, sourceLocale?: string\|null, fast?: bool, reference?: array<string,mixed>\|null}** | Chat translation options defining locale behavior and context:
- 'targetLocale' (string, required): Language code to translate messages into (e.g., 'es', 'fr')
- 'sourceLocale' (string\|null): Language code of original messages, null for auto-detection
- 'fast' (bool): Trade translation quality for speed
- 'reference' (array<string, mixed>\|null): Context data or glossary terms to guide translation |
| `$progressCallback` | **callable\|null**                                                                                               | Called with completion percentage (0-100) during processing                                                                                                                                                                                                                                                                                                                                                       |

**Return Value:**

Translated messages keeping original speaker names unchanged

Example:
    $conversation = [
        ['name' => 'Alice', 'text' => 'Hello, how are you?'],
        ['name' => 'Bob', 'text' => 'I am fine, thank you!'],
    ];
    $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
    $engine->localizeChat($conversation, ['sourceLocale' => 'en', 'targetLocale' => 'de']);

**Throws:**

When chat entries or params are invalid
- [`InvalidArgumentException`](../../InvalidArgumentException)
When API rejects or fails to process the request
- [`RuntimeException`](../../RuntimeException)

***

### recognizeLocale

Identify the locale of the provided text.

```php
public recognizeLocale(string $text): string
```

**Parameters:**

| Parameter | Type       | Description                                                        |
|-----------|------------|--------------------------------------------------------------------|
| `$text`   | **string** | Sample text for language detection (longer text improves accuracy) |

**Return Value:**

ISO language code detected by the API (e.g., 'en', 'es', 'zh')

Example:
    $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
    $engine->recognizeLocale('Bonjour le monde');

**Throws:**

When input text is blank after trimming
- [`InvalidArgumentException`](../../InvalidArgumentException)
When API response is invalid or request fails
- [`RuntimeException`](../../RuntimeException)

***
