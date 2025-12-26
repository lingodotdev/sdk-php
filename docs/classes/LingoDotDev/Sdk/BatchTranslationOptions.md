
Options for batch text translation to multiple languages.

***

* Full name: `\LingoDotDev\Sdk\BatchTranslationOptions`

**See Also:**

* https://lingo.dev

## Properties

### sourceLocale

Source language code (e.g., 'en')
Required field.

```php
public string $sourceLocale
```

***

### targetLocales

Array of target language codes (e.g., ['es', 'fr', 'de'])
Required field.

```php
public string[] $targetLocales
```

***

### fast

Enable fast mode - trades translation quality for speed
Default: false (quality mode)

```php
public bool $fast
```

***

## Methods

### create

Create a fluent builder for batch translation options.

```php
public static create(string $sourceLocale): self
```

* This method is **static**.
**Parameters:**

| Parameter       | Type       | Description          |
|-----------------|------------|----------------------|
| `$sourceLocale` | **string** | Source language code |

***

### to

Set target locales.

```php
public to(string[] $locales): self
```

**Parameters:**

| Parameter  | Type         | Description           |
|------------|--------------|-----------------------|
| `$locales` | **string[]** | Target language codes |

***

### addTarget

Add a single target locale.

```php
public addTarget(string $locale): self
```

**Parameters:**

| Parameter | Type       | Description          |
|-----------|------------|----------------------|
| `$locale` | **string** | Target language code |

***

### withFastMode

Enable fast translation mode.

```php
public withFastMode(): self
```

***
