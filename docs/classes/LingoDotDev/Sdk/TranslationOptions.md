
Options for text and object translation.

***

* Full name: `\LingoDotDev\Sdk\TranslationOptions`

**See Also:**

* https://lingo.dev

## Properties

### targetLocale

Target language code (e.g., 'es', 'fr')
Required field.

```php
public string $targetLocale
```

***

### sourceLocale

Source language code or null for auto-detection
Example: 'en', 'es', or null

```php
public ?string $sourceLocale
```

***

### fast

Enable fast mode - trades translation quality for speed
Default: false (quality mode)

```php
public bool $fast
```

***

### reference

Context data or glossary terms to guide translation
Can include domain-specific terminology or reference translations

```php
public array<string,mixed>|null $reference
```

***

## Methods

### create

Create a fluent builder for translation options.

```php
public static create(string $targetLocale): self
```

* This method is **static**.
**Parameters:**

| Parameter       | Type       | Description          |
|-----------------|------------|----------------------|
| `$targetLocale` | **string** | Target language code |

***

### from

Set source locale.

```php
public from(string|null $locale): self
```

**Parameters:**

| Parameter | Type             | Description                                     |
|-----------|------------------|-------------------------------------------------|
| `$locale` | **string\|null** | Source language code or null for auto-detection |

***

### withFastMode

Enable fast translation mode.

```php
public withFastMode(): self
```

***

### withReference

Add reference data for context.

```php
public withReference(array<string,mixed> $reference): self
```

**Parameters:**

| Parameter    | Type                    | Description              |
|--------------|-------------------------|--------------------------|
| `$reference` | **array<string,mixed>** | Context or glossary data |

***
