<?php
/**
 * PHP SDK for Lingo.dev
 *
 * @category Localization
 * @package  Lingodotdev\Sdk
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */

namespace LingoDotDev\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Respect\Validation\Validator as v;

/**
 * LingoDotDevEngine wraps the Lingo.dev localization API for PHP consumers.
 *
 * Use a single engine instance to translate strings, arrays, and chat logs, or
 * to detect the locale of free-form text. The engine handles request batching,
 * progress reporting, and surfacing validation or transport errors.
 *
 * Example (basic setup):
 *     $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
 *     $engine = new LingoDotDevEngine($config);
 *
 * Example (Laravel integration):
 *     $config = EngineConfig::create(config('services.lingodotdev.api_key'))
 *         ->withBatchSize(100);
 *     $engine = new LingoDotDevEngine($config);
 *
 *     $options = TranslationOptions::create('es')->from('en');
 *     $engine->localizeText($request->message, $options);
 *
 * @category Localization
 * @package  Lingodotdev\Sdk
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */
class LingoDotDevEngine
{
    /**
     * Configuration options for the Engine.
     *
     * @var EngineConfig
     */
    protected EngineConfig $config;

    /**
     * HTTP client for API requests.
     *
     * @var Client
     */
    private $_httpClient;

    /**
     * Build an engine with your configuration.
     *
     * Example:
     *     $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY'])
     *         ->withBatchSize(100)
     *         ->withIdealBatchItemSize(1000);
     *     $engine = new LingoDotDevEngine($config);
     *
     * @param EngineConfig $config Engine configuration
     *
     * @throws \InvalidArgumentException Invalid configuration values
     */
    public function __construct(EngineConfig $config)
    {
        $this->config = $config;

        if (empty($this->config->apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }

        if (!filter_var($this->config->apiUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('API URL must be a valid URL');
        }

        if ($this->config->batchSize <= 0 || $this->config->batchSize > 250) {
            throw new \InvalidArgumentException('Batch size must be between 1 and 250');
        }

        if ($this->config->idealBatchItemSize <= 0 || $this->config->idealBatchItemSize > 2500) {
            throw new \InvalidArgumentException('Ideal batch item size must be between 1 and 2500');
        }

        $this->_httpClient = new Client(
            [
            'base_uri' => $this->config->apiUrl,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $this->config->apiKey
            ]
            ]
        );
    }

    /**
     * Localize content using the Lingo.dev API.
     *
     * @param array<string, mixed> $payload Content to translate
     * @param TranslationOptions $options Translation configuration
     * @param callable|null $progressCallback Progress callback (0-100%, chunk, result)
     *
     * @return array<string, mixed> Translated content
     *
     * @internal
     */
    protected function localizeRaw(array $payload, TranslationOptions $options, ?callable $progressCallback = null): array
    {
        $chunkedPayload = $this->_extractPayloadChunks($payload);
        $processedPayloadChunks = [];

        $workflowId = $this->_createId();

        for ($i = 0; $i < count($chunkedPayload); $i++) {
            $chunk = $chunkedPayload[$i];
            $percentageCompleted = round((($i + 1) / count($chunkedPayload)) * 100);

            $processedPayloadChunk = $this->_localizeChunk(
                $options->sourceLocale,
                $options->targetLocale,
                [
                    'data' => $chunk,
                    'reference' => $options->reference
                ],
                $workflowId,
                $options->fast
            );

            if ($progressCallback) {
                $progressCallback($percentageCompleted, $chunk, $processedPayloadChunk);
            }

            $processedPayloadChunks[] = $processedPayloadChunk;
        }

        return array_merge(...$processedPayloadChunks);
    }

    /**
     * Localize a single chunk of content.
     *
     * @param string|null $sourceLocale Source language code or null for auto-detect
     * @param string $targetLocale Target language code
     * @param array{data: array<string, mixed>, reference?: array<string, mixed>|null} $payload Content chunk with optional reference
     * @param string $workflowId Workflow tracking ID
     * @param bool $fast Fast mode flag
     *
     * @return array<string, mixed> Translated chunk
     *
     * @throws \InvalidArgumentException Invalid reference format
     * @throws \RuntimeException API request failure
     */
    private function _localizeChunk(?string $sourceLocale, string $targetLocale, array $payload, string $workflowId, bool $fast): array
    {
        try {
            $requestBody = [
                'params' => [
                    'workflowId' => $workflowId,
                    'fast' => $fast
                ],
                'locale' => [
                    'source' => $sourceLocale,
                    'target' => $targetLocale
                ],
                'data' => $payload['data']
            ];
            
            if (isset($payload['reference']) && $payload['reference'] !== null) {
                if (!is_array($payload['reference'])) {
                    throw new \InvalidArgumentException('Reference must be an array');
                }
                $requestBody['reference'] = $payload['reference'];
            } else {
                $requestBody['reference'] = (object)[];
            }
            
            $response = $this->_httpClient->post(
                '/i18n', [
                'json' => $requestBody
                ]
            );

            $jsonResponse = json_decode($response->getBody()->getContents(), true);

            if (!isset($jsonResponse['data']) && isset($jsonResponse['error'])) {
                throw new \RuntimeException($jsonResponse['error']);
            }

            return $jsonResponse['data'] ?? [];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = $e->getResponse()->getBody()->getContents();
                
                if ($statusCode === 400) {
                    throw new \InvalidArgumentException('Invalid request: ' . $e->getMessage());
                } else {
                    $errorData = json_decode($responseBody, true);
                    $errorMessage = isset($errorData['message']) ? $errorData['message'] : $e->getMessage();
                    throw new \RuntimeException($errorMessage);
                }
            }
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Extract payload chunks based on the ideal chunk size.
     *
     * @param array<string, mixed> $payload The payload to be chunked
     *
     * @return array<int, array<string, mixed>> Array of payload chunks
     */
    private function _extractPayloadChunks(array $payload): array
    {
        $result = [];
        $currentChunk = [];
        $currentChunkItemCount = 0;

        $payloadEntries = $payload;
        $keys = array_keys($payloadEntries);
        
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            $value = $payloadEntries[$key];
            
            $currentChunk[$key] = $value;
            $currentChunkItemCount++;

            $currentChunkSize = $this->_countWordsInRecord($currentChunk);

            if ($currentChunkSize > $this->config->idealBatchItemSize
                || $currentChunkItemCount >= $this->config->batchSize
                || $i === count($keys) - 1
            ) {
                $result[] = $currentChunk;
                $currentChunk = [];
                $currentChunkItemCount = 0;
            }
        }

        return $result;
    }

    /**
     * Count words in a record or array.
     *
     * @param mixed $payload The payload to count words in
     *
     * @return int Total number of words
     */
    private function _countWordsInRecord($payload): int
    {
        if (is_array($payload)) {
            $count = 0;
            foreach ($payload as $item) {
                $count += $this->_countWordsInRecord($item);
            }
            return $count;
        } elseif (is_object($payload)) {
            $count = 0;
            foreach ((array)$payload as $item) {
                $count += $this->_countWordsInRecord($item);
            }
            return $count;
        } elseif (is_string($payload)) {
            return count(array_filter(explode(' ', trim($payload))));
        } else {
            return 0;
        }
    }

    /**
     * Generate a unique ID.
     *
     * @return string Unique ID
     */
    private function _createId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Localize every string in a nested array while keeping its shape intact.
     *
     * Example:
     *     $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
     *     $engine = new LingoDotDevEngine($config);
     *     $options = TranslationOptions::create('fr')->from('en');
     *     $engine->localizeObject(['greeting' => 'Hello'], $options);
     *
     * @param array<string, mixed> $obj Nested data structure to translate
     * @param TranslationOptions $options Translation options
     * @param callable|null $progressCallback Progress callback (%, batch, result)
     *
     * @return array<string, mixed> Translated data preserving structure
     *
     * @throws \RuntimeException API request failure
     */
    public function localizeObject(array $obj, TranslationOptions $options, ?callable $progressCallback = null): array
    {
        return $this->localizeRaw($obj, $options, function($progress, $chunk, $processedChunk) use ($progressCallback) {
            if ($progressCallback) {
                $progressCallback($progress, $chunk, $processedChunk);
            }
        });
    }

    /**
     * Localize a single string and return the translated text.
     *
     * Examples:
     *     $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
     *     $engine = new LingoDotDevEngine($config);
     *
     *     // Simple translation
     *     $options = TranslationOptions::create('es')->from('en');
     *     $engine->localizeText('Hello, world!', $options);
     *
     *     // With progress callback
     *     $engine->localizeText(
     *         'This is a very long text...',
     *         $options,
     *         function (int $progress): void {
     *             echo "Progress: {$progress}%\n";
     *         }
     *     );
     *
     *     // Auto-detect source language
     *     $options = TranslationOptions::create('en');
     *     $engine->localizeText('Bonjour le monde', $options);
     *
     * @param string $text Text to translate
     * @param TranslationOptions $options Translation options
     * @param callable|null $progressCallback Progress callback (0-100%)
     *
     * @return string Translated text or empty string
     *
     * @throws \RuntimeException API request failure
     */
    public function localizeText(string $text, TranslationOptions $options, ?callable $progressCallback = null): string
    {
        $response = $this->localizeRaw(['text' => $text], $options, function($progress, $chunk, $processedChunk) use ($progressCallback) {
            if ($progressCallback) {
                $progressCallback($progress);
            }
        });

        return $response['text'] ?? '';
    }

    /**
     * Localize a string into multiple languages and return texts in order.
     *
     * Example:
     *     $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
     *     $engine = new LingoDotDevEngine($config);
     *
     *     $options = BatchTranslationOptions::create('en')
     *         ->to(['es', 'fr', 'de'])
     *         ->withFastMode();
     *     $engine->batchLocalizeText('Hello, world!', $options);
     *
     * @param string $text Text to translate
     * @param BatchTranslationOptions $options Batch translation options
     *
     * @return string[] Translated texts in targetLocales order
     *
     * @throws \RuntimeException Individual request failure
     */
    public function batchLocalizeText(string $text, BatchTranslationOptions $options): array
    {
        $responses = [];
        foreach ($options->targetLocales as $targetLocale) {
            $translationOptions = TranslationOptions::create($targetLocale)
                ->from($options->sourceLocale);

            if ($options->fast) {
                $translationOptions->withFastMode();
            }

            $responses[] = $this->localizeText($text, $translationOptions);
        }

        return $responses;
    }

    /**
     * Localize a chat transcript while preserving speaker names.
     *
     * Example:
     *     $conversation = [
     *         ['name' => 'Alice', 'text' => 'Hello, how are you?'],
     *         ['name' => 'Bob', 'text' => 'I am fine, thank you!'],
     *     ];
     *
     *     $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
     *     $engine = new LingoDotDevEngine($config);
     *     $options = TranslationOptions::create('de')->from('en');
     *     $engine->localizeChat($conversation, $options);
     *
     * @param array<int, array{name: string, text: string}> $chat Conversation with names and messages
     * @param TranslationOptions $options Translation options
     * @param callable|null $progressCallback Progress callback (0-100%)
     *
     * @return array<int, array{name: string, text: string}> Translated chat preserving names
     *
     * @throws \InvalidArgumentException Invalid chat entries
     * @throws \RuntimeException API request failure
     */
    public function localizeChat(array $chat, TranslationOptions $options, ?callable $progressCallback = null): array
    {
        foreach ($chat as $message) {
            if (!isset($message['name']) || !isset($message['text'])) {
                throw new \InvalidArgumentException('Each chat message must have name and text properties');
            }
        }

        $localized = $this->localizeRaw(['chat' => $chat], $options, function($progress, $chunk, $processedChunk) use ($progressCallback) {
            if ($progressCallback) {
                $progressCallback($progress);
            }
        });

        $result = [];
        if (isset($localized['chat']) && is_array($localized['chat'])) {
            foreach ($localized['chat'] as $index => $message) {
                if (isset($chat[$index]['name']) && isset($message['text'])) {
                    $result[] = [
                        'name' => $chat[$index]['name'],
                        'text' => $message['text']
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Identify the locale of the provided text.
     *
     * Example:
     *     $config = EngineConfig::create($_ENV['LINGODOTDEV_API_KEY']);
     *     $engine = new LingoDotDevEngine($config);
     *     $engine->recognizeLocale('Bonjour le monde');
     *
     * @param string $text Sample text for language detection
     *
     * @return string ISO language code (e.g., 'en', 'es', 'zh')
     *
     * @throws \InvalidArgumentException Empty text provided
     * @throws \RuntimeException Invalid API response or request failure
     */
    public function recognizeLocale(string $text): string
    {
        if (empty(trim($text))) {
            throw new \InvalidArgumentException('Text cannot be empty');
        }
        
        try {
            $response = $this->_httpClient->post(
                '/recognize', [
                'json' => ['text' => $text]
                ]
            );

            $jsonResponse = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($jsonResponse['locale'])) {
                throw new \RuntimeException('Invalid response from API: locale not found');
            }
            
            return $jsonResponse['locale'];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : $e->getMessage();
                throw new \RuntimeException('Error recognizing locale: ' . $errorMessage);
            }
            throw new \RuntimeException('Error recognizing locale: ' . $e->getMessage());
        }
    }
}
