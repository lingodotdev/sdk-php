# Lingo.dev PHP SDK

Official PHP SDK for Lingo.dev, a powerful localization engine that supports various content types including plain text, objects, and chat sequences.

## Installation

You can install the SDK via Composer:

```bash
composer require lingodotdev/sdk
```

## Requirements

- PHP 8.1 or higher
- Composer
- GuzzleHttp Client
- Respect Validation

## Basic Usage

### Initialize the SDK

```php
<?php

use LingoDotDev\Sdk\LingoDotDevEngine;

// Initialize the SDK with your API key
$engine = new LingoDotDevEngine([
    'apiKey' => 'your-api-key',
]);
```

### Text Localization

Translate a simple text string from one language to another:

```php
// Localize a text string from English to Spanish
$localizedText = $engine->localizeText('Hello, world!', [
    'sourceLocale' => 'en',
    'targetLocale' => 'es',
]);
// Output: "¡Hola, mundo!"
```

### Object Localization

Translate an array of strings while preserving the structure:

```php
// Localize an object from English to French
$localizedObject = $engine->localizeObject([
    'greeting' => 'Hello',
    'farewell' => 'Goodbye',
    'messages' => [
        'welcome' => 'Welcome to our service',
        'thanks' => 'Thank you for your business'
    ]
], [
    'sourceLocale' => 'en',
    'targetLocale' => 'fr',
]);
/* Output:
[
    'greeting' => 'Bonjour',
    'farewell' => 'Au revoir',
    'messages' => [
        'welcome' => 'Bienvenue dans notre service',
        'thanks' => 'Merci pour votre confiance'
    ]
]
*/
```

### Chat Localization

Translate a chat conversation while preserving speaker names:

```php
// Localize a chat conversation from English to German
$localizedChat = $engine->localizeChat([
    ['name' => 'Alice', 'text' => 'Hello, how are you?'],
    ['name' => 'Bob', 'text' => 'I am fine, thank you!'],
    ['name' => 'Alice', 'text' => 'What are you doing today?']
], [
    'sourceLocale' => 'en',
    'targetLocale' => 'de',
]);
/* Output:
[
    ['name' => 'Alice', 'text' => 'Hallo, wie geht es dir?'],
    ['name' => 'Bob', 'text' => 'Mir geht es gut, danke!'],
    ['name' => 'Alice', 'text' => 'Was machst du heute?']
]
*/
```

### Language Detection

Detect the language of a given text:

```php
// Detect language
$locale = $engine->recognizeLocale('Bonjour le monde');
// Output: "fr"
```

### Batch Localization

Translate a text to multiple languages at once:

```php
// Batch localize text to multiple languages
$localizedTexts = $engine->batchLocalizeText('Hello, world!', [
    'sourceLocale' => 'en',
    'targetLocales' => ['es', 'fr', 'de', 'it'],
]);
/* Output:
[
    "¡Hola, mundo!",
    "Bonjour le monde!",
    "Hallo, Welt!",
    "Ciao, mondo!"
]
*/
```

### Progress Tracking

Track the progress of a localization operation:

```php
// Localize with progress tracking
$engine->localizeText('Hello, world!', [
    'sourceLocale' => 'en',
    'targetLocale' => 'es',
], function ($progress, $chunk, $processedChunk) {
    echo "Localization progress: $progress%\n";
});
```

## Advanced Configuration

You can customize the SDK behavior with additional configuration options:

```php
$engine = new LingoDotDevEngine([
    'apiKey' => 'your-api-key',
    'apiUrl' => 'https://custom-engine.lingo.dev', // Custom API URL
    'batchSize' => 50,                            // Custom batch size (1-250)
    'idealBatchItemSize' => 500                   // Custom batch item size (1-2500)
]);
```

## Release Process

The SDK uses semantic versioning (MAJOR.MINOR.PATCH) and is automatically published to Packagist when changes are merged to the main branch. The release process includes:

1. Running tests to ensure code quality
2. Automatically bumping the patch version
3. Creating a git tag for the new version
4. Publishing the package to Packagist

Packagist automatically fetches new versions from the GitHub repository when tags are pushed, making the new version immediately available for installation via Composer.

## Documentation

For more detailed documentation, visit the [Lingo.dev Documentation](https://lingo.dev/go/docs).

## License

This SDK is released under the MIT License.
