# Lingo.dev PHP SDK

Official PHP SDK for Lingo.dev, a powerful localization engine that supports various content types including plain text, objects, and chat sequences.

## Installation

You can install the SDK via Composer:

```bash
composer require lingodotdev/sdk
```

## Basic Usage

After installing the package, bootstrap the engine with your API key:

```php
require 'vendor/autoload.php';

use LingoDotDev\Sdk\LingoDotDevEngine;

$engine = new LingoDotDevEngine([
    'apiKey' => 'your-api-key',       // replace with your actual key
    'engineId' => 'your-engine-id',   // optional — override the default engine
]);
```

### Configuration Options

| Option | Type | Required | Default | Description |
|---|---|---|---|---|
| `apiKey` | string | Yes | — | Your Lingo.dev API key |
| `engineId` | string | No | — | Your Lingo.dev Engine ID |
| `apiUrl` | string | No | `https://api.lingo.dev` | API base URL |
| `batchSize` | int | No | `25` | Max items per chunk (1–250) |
| `idealBatchItemSize` | int | No | `250` | Max words per chunk (1–2500) |

### Scenarios demonstrated in this README

1. Text Localization
2. Object Localization
3. Chat Localization
4. Batch Localization
5. Language Detection
6. Progress Tracking

## Requirements

- PHP 8.1 or higher
- Composer
- GuzzleHttp Client

## Getting Started

### Creating a New PHP Project with Lingo.dev SDK

Follow these steps to create a new PHP project that uses the Lingo.dev SDK:

1. **Create a project directory**:

   ```bash
   mkdir my-lingo-project
   cd my-lingo-project
   ```

2. **Initialize Composer**:

   ```bash
   composer init --name=your-vendor/your-project --description="Your project description" --type=project --require="php:^8.1" --author="Your Name <your.email@example.com>"
   ```

3. **Add Lingo.dev SDK as a dependency**:

   ```bash
   composer require lingodotdev/sdk
   ```

## API Scenarios

### Initialize the SDK

```php
<?php

use LingoDotDev\Sdk\LingoDotDevEngine;

// Initialize the SDK with your API key
$engine = new LingoDotDevEngine([
    'apiKey' => 'your-api-key',
    'engineId' => 'your-engine-id',   // optional
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

You can pass a reference to provide additional context:

```php
// Localize with reference for additional context
$localizedObject = $engine->localizeObject([
    'greeting' => 'Hello',
], [
    'sourceLocale' => 'en',
    'targetLocale' => 'es',
    'reference' => [
        'fr' => ['greeting' => 'Bonjour']
    ],
]);
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
], function ($progress) {
    echo "Localization progress: $progress%\n";
});
```

## Demo App

If you prefer to start with a minimal example instead of the detailed scenarios above, create **index.php** in an empty folder, copy the following snippet, install dependencies with `composer require lingodotdev/sdk`, set `LINGODOTDEV_API_KEY` (and optionally `LINGODOTDEV_ENGINE_ID`), and run `php index.php`.

Want to see everything in action?

1. Clone this repository or copy the `index.php` from the **demo** below into an empty directory.
2. Run `composer install` to pull in the SDK.
3. Populate the `LINGODOTDEV_API_KEY` environment variable (and optionally `LINGODOTDEV_ENGINE_ID`).
4. Execute the script with `php index.php` and observe the output.

`index.php` demo:

```php
<?php

require 'vendor/autoload.php';

use LingoDotDev\Sdk\LingoDotDevEngine;

$config = [
    'apiKey' => getenv('LINGODOTDEV_API_KEY'),
];
if (getenv('LINGODOTDEV_ENGINE_ID')) {
    $config['engineId'] = getenv('LINGODOTDEV_ENGINE_ID');
}
$engine = new LingoDotDevEngine($config);

// 1. Text
$helloEs = $engine->localizeText('Hello world!', [
    'sourceLocale' => 'en',
    'targetLocale' => 'es',
]);
echo "Text ES: $helloEs\n\n";

// 2. Object
$objectFr = $engine->localizeObject([
    'greeting' => 'Good morning',
    'farewell' => 'Good night',
], [
    'sourceLocale' => 'en',
    'targetLocale' => 'fr',
]);
print_r($objectFr);

// 3. Chat
$chatJa = $engine->localizeChat([
    ['name' => 'Alice', 'text' => 'Hi'],
    ['name' => 'Bob', 'text' => 'Hello!'],
], [
    'sourceLocale' => 'en',
    'targetLocale' => 'ja',
]);
print_r($chatJa);

// 4. Detect language
$lang = $engine->recognizeLocale('Ciao mondo');
echo "Detected: $lang\n";
```

---

## Release Process

The SDK uses semantic versioning (MAJOR.MINOR.PATCH) and is automatically published to Packagist when changes are merged to the main branch. The release process includes:

1. Running tests to ensure code quality
2. Detecting the current version from git tags
3. Automatically bumping the patch version
4. Creating a new git tag for the new version

Packagist automatically fetches new versions from the GitHub repository when tags are pushed, making the new version immediately available for installation via Composer. The version is determined by git tags rather than being stored in composer.json, following Packagist best practices.

## Documentation

For more detailed documentation, visit the [Lingo.dev Documentation](https://lingo.dev/go/docs).

## License

This SDK is released under the MIT License.
