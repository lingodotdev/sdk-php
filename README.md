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

4. **Create a simple PHP script** (index.php):
   ```php
   <?php
   
   require 'vendor/autoload.php';
   
   use LingoDotDev\Sdk\LingoDotDevEngine;
   
   // Get API key from environment variable or command line
   $apiKey = getenv('LINGODOTDEV_API_KEY') ?: $argv[1] ?? null;
   
   if (!$apiKey) {
       echo "Error: API key is required. Set LINGODOTDEV_API_KEY environment variable or pass it as a command-line argument.\n";
       exit(1);
   }
   
   // Initialize the SDK
   $engine = new LingoDotDevEngine([
       'apiKey' => $apiKey,
   ]);
   
   // Make your first localization call
   try {
       $result = $engine->localizeText('Hello, this is my first localization with Lingo.dev!', [
           'sourceLocale' => 'en',
           'targetLocale' => 'es',
       ]);
       
       echo "Original: Hello, this is my first localization with Lingo.dev!\n";
       echo "Translated to Spanish: $result\n";
   } catch (\Exception $e) {
       echo "Error: " . $e->getMessage() . "\n";
   }
   ```

5. **Run your script**:
   ```bash
   # Option 1: Pass API key as command-line argument
   php index.php your-api-key-here
   
   # Option 2: Set environment variable and run
   export LINGODOTDEV_API_KEY=your-api-key-here
   php index.php
   ```

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

## Release Process

The SDK uses semantic versioning (MAJOR.MINOR.PATCH) and is automatically published to Packagist when changes are merged to the main branch. The release process includes:

1. Running tests to ensure code quality
2. Automatically bumping the patch version
3. Creating a git tag for the new version

Packagist automatically fetches new versions from the GitHub repository when tags are pushed, making the new version immediately available for installation via Composer.

## Documentation

For more detailed documentation, visit the [Lingo.dev Documentation](https://lingo.dev/go/docs).

## License

This SDK is released under the MIT License.
