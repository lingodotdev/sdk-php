
Configuration for LingoDotDevEngine initialization.

***

* Full name: `\LingoDotDev\Sdk\EngineConfig`

**See Also:**

* https://lingo.dev

## Properties

### apiKey

Your Lingo.dev API token

```php
public string $apiKey
```

***

### apiUrl

API base URL (default: https://engine.lingo.dev)

```php
public string $apiUrl
```

***

### batchSize

Maximum records per request (1-250, default: 25)

```php
public int $batchSize
```

***

### idealBatchItemSize

Maximum words per request (1-2500, default: 250)

```php
public int $idealBatchItemSize
```

***

## Methods

### create

Create configuration with API key.

```php
public static create(string $apiKey): self
```

* This method is **static**.
**Parameters:**

| Parameter | Type       | Description              |
|-----------|------------|--------------------------|
| `$apiKey` | **string** | Your Lingo.dev API token |

***

### withApiUrl

Set custom API URL.

```php
public withApiUrl(string $url): self
```

**Parameters:**

| Parameter | Type       | Description      |
|-----------|------------|------------------|
| `$url`    | **string** | API endpoint URL |

***

### withBatchSize

Set batch size limit.

```php
public withBatchSize(int $size): self
```

**Parameters:**

| Parameter | Type    | Description                 |
|-----------|---------|-----------------------------|
| `$size`   | **int** | Records per request (1-250) |

***

### withIdealBatchItemSize

Set ideal batch item size.

```php
public withIdealBatchItemSize(int $size): self
```

**Parameters:**

| Parameter | Type    | Description                    |
|-----------|---------|--------------------------------|
| `$size`   | **int** | Max words per request (1-2500) |

***
