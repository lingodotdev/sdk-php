
LingoDotDevEngine wraps the Lingo.dev localization API for PHP consumers.

Use a single engine instance to translate strings, arrays, and chat logs, or
to detect the locale of free-form text. The engine handles request batching,
progress reporting, and surfacing validation or transport errors.

Example (basic setup):
    $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
    $engine = new LingoDotDevEngine($config);

Example (Laravel integration):
    $config = EngineConfig::create(config('services.lingodotdev.api_key'))
        ->withBatchSize(100);
    $engine = new LingoDotDevEngine($config);

    $options = TranslationOptions::create('es')->from('en');
    $engine->localizeText($request->message, $options);

***

* Full name: `\LingoDotDev\Sdk\LingoDotDevEngine`

**See Also:**

* https://lingo.dev

## Methods

### __construct

Build an engine with your configuration.

```php
public __construct(\LingoDotDev\Sdk\EngineConfig $config): mixed
```

Example:
$config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY'])
    ->withBatchSize(100)
    ->withIdealBatchItemSize(1000);
$engine = new LingoDotDevEngine($config);

**Parameters:**

| Parameter | Type                              | Description          |
|-----------|-----------------------------------|----------------------|
| `$config` | **\LingoDotDev\Sdk\EngineConfig** | Engine configuration |

**Throws:**

Invalid configuration values
- [`InvalidArgumentException`](../../InvalidArgumentException)

***

### localizeObject

Localize every string in a nested array while keeping its shape intact.

```php
public localizeObject(array<string,mixed> $obj, \LingoDotDev\Sdk\TranslationOptions $options, callable|null $progressCallback = null): array<string,mixed>
```

Example:
$config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
$engine = new LingoDotDevEngine($config);
$options = TranslationOptions::create('fr')->from('en');
$engine->localizeObject(['greeting' => 'Hello'], $options);

**Parameters:**

| Parameter           | Type                                    | Description                          |
|---------------------|-----------------------------------------|--------------------------------------|
| `$obj`              | **array<string,mixed>**                 | Nested data structure to translate   |
| `$options`          | **\LingoDotDev\Sdk\TranslationOptions** | Translation options                  |
| `$progressCallback` | **callable\|null**                      | Progress callback (%, batch, result) |

**Return Value:**

Translated data preserving structure

**Throws:**

API request failure
- [`RuntimeException`](../../RuntimeException)

***

### localizeText

Localize a single string and return the translated text.

```php
public localizeText(string $text, \LingoDotDev\Sdk\TranslationOptions $options, callable|null $progressCallback = null): string
```

Examples:
$config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
$engine = new LingoDotDevEngine($config);

// Simple translation
$options = TranslationOptions::create('es')->from('en');
$engine->localizeText('Hello, world!', $options);

// With progress callback
$engine->localizeText(
    'This is a very long text...',
    $options,
    function (int $progress): void {
        echo "Progress: {$progress}%%\n";
    }
);

// Auto-detect source language
$options = TranslationOptions::create('en');
$engine->localizeText('Bonjour le monde', $options);

**Parameters:**

| Parameter           | Type                                    | Description                |
|---------------------|-----------------------------------------|----------------------------|
| `$text`             | **string**                              | Text to translate          |
| `$options`          | **\LingoDotDev\Sdk\TranslationOptions** | Translation options        |
| `$progressCallback` | **callable\|null**                      | Progress callback (0-100%) |

**Return Value:**

Translated text or empty string

**Throws:**

API request failure
- [`RuntimeException`](../../RuntimeException)

***

### batchLocalizeText

Localize a string into multiple languages and return texts in order.

```php
public batchLocalizeText(string $text, \LingoDotDev\Sdk\BatchTranslationOptions $options): string[]
```

Example:
$config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
$engine = new LingoDotDevEngine($config);

$options = BatchTranslationOptions::create('en')
    ->to(['es', 'fr', 'de'])
    ->withFastMode();
$engine->batchLocalizeText('Hello, world!', $options);

**Parameters:**

| Parameter  | Type                                         | Description               |
|------------|----------------------------------------------|---------------------------|
| `$text`    | **string**                                   | Text to translate         |
| `$options` | **\LingoDotDev\Sdk\BatchTranslationOptions** | Batch translation options |

**Return Value:**

Translated texts in targetLocales order

**Throws:**

Individual request failure
- [`RuntimeException`](../../RuntimeException)

***

### localizeChat

Localize a chat transcript while preserving speaker names.

```php
public localizeChat(array<int,array{name: string, text: string}> $chat, \LingoDotDev\Sdk\TranslationOptions $options, callable|null $progressCallback = null): array<int,array{name: string, text: string}>
```

Example:
$conversation = [
    ['name' => 'Alice', 'text' => 'Hello, how are you?'],
    ['name' => 'Bob', 'text' => 'I am fine, thank you!'],
];

$config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
$engine = new LingoDotDevEngine($config);
$options = TranslationOptions::create('de')->from('en');
$engine->localizeChat($conversation, $options);

**Parameters:**

| Parameter           | Type                                             | Description                          |
|---------------------|--------------------------------------------------|--------------------------------------|
| `$chat`             | **array<int,array{name: string, text: string}>** | Conversation with names and messages |
| `$options`          | **\LingoDotDev\Sdk\TranslationOptions**          | Translation options                  |
| `$progressCallback` | **callable\|null**                               | Progress callback (0-100%)           |

**Return Value:**

Translated chat preserving names

**Throws:**

Invalid chat entries
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
$config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
$engine = new LingoDotDevEngine($config);
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
