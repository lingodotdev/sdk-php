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
 *     $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
 *
 * Example (Laravel integration):
 *     $engine = new LingoDotDevEngine(['apiKey' => config('services.lingodotdev.api_key')]);
 *     $engine->localizeText($request->message, ['sourceLocale' => 'en', 'targetLocale' => 'es']);
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
     * @var array{apiKey: string, apiUrl: string, batchSize: int, idealBatchItemSize: int}
     */
    protected $config;

    /**
     * HTTP client for API requests.
     *
     * @var Client
     */
    private $_httpClient;

    /**
     * Build an engine with your API key and optional batching limits.
     *
     * Example:
     *     $engine = new LingoDotDevEngine([
     *         'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     *         'batchSize' => 100,
     *         'idealBatchItemSize' => 1000,
     *     ]);
     *
     * @param array{apiKey: string, apiUrl?: string, batchSize?: int, idealBatchItemSize?: int} $config Configuration options
     *
     * @throws \InvalidArgumentException API key missing or values invalid
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            [
            'apiUrl' => 'https://engine.lingo.dev',
            'batchSize' => 25,
            'idealBatchItemSize' => 250
            ], $config
        );

        if (!isset($this->config['apiKey'])) {
            throw new \InvalidArgumentException('API key is required');
        }

        if (!filter_var($this->config['apiUrl'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('API URL must be a valid URL');
        }

        if (!is_int($this->config['batchSize']) || $this->config['batchSize'] <= 0 || $this->config['batchSize'] > 250) {
            throw new \InvalidArgumentException('Batch size must be an integer between 1 and 250');
        }

        if (!is_int($this->config['idealBatchItemSize']) || $this->config['idealBatchItemSize'] <= 0 || $this->config['idealBatchItemSize'] > 2500) {
            throw new \InvalidArgumentException('Ideal batch item size must be an integer between 1 and 2500');
        }

        $this->_httpClient = new Client(
            [
            'base_uri' => $this->config['apiUrl'],
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $this->config['apiKey']
            ]
            ]
        );
    }

    /**
     * Localize content using the Lingo.dev API.
     *
     * @param array<string, mixed> $payload Content to translate
     * @param array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string, mixed>|null} $params Translation configuration
     * @param callable|null $progressCallback Progress callback (0-100%, chunk, result)
     *
     * @return array<string, mixed> Translated content
     *
     * @internal
     */
    protected function localizeRaw(array $payload, array $params, ?callable $progressCallback = null): array
    {
        if (!isset($params['targetLocale'])) {
            throw new \InvalidArgumentException('Target locale is required');
        }

        if (isset($params['sourceLocale']) && !is_string($params['sourceLocale']) && $params['sourceLocale'] !== null) {
            throw new \InvalidArgumentException('Source locale must be a string or null');
        }

        if (!is_string($params['targetLocale'])) {
            throw new \InvalidArgumentException('Target locale must be a string');
        }

        $chunkedPayload = $this->_extractPayloadChunks($payload);
        $processedPayloadChunks = [];

        $workflowId = $this->_createId();

        for ($i = 0; $i < count($chunkedPayload); $i++) {
            $chunk = $chunkedPayload[$i];
            $percentageCompleted = round((($i + 1) / count($chunkedPayload)) * 100);

            $processedPayloadChunk = $this->_localizeChunk(
                $params['sourceLocale'] ?? null,
                $params['targetLocale'],
                [
                    'data' => $chunk, 
                    'reference' => $params['reference'] ?? null
                ],
                $workflowId,
                $params['fast'] ?? false
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
            
            if ($currentChunkSize > $this->config['idealBatchItemSize'] 
                || $currentChunkItemCount >= $this->config['batchSize'] 
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
     *     $content = ['greeting' => 'Hello'];
     *     $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
     *     $engine->localizeObject($content, ['sourceLocale' => 'en', 'targetLocale' => 'fr']);
     *
     * @param array<string, mixed> $obj Nested data structure to translate
     * @param array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string, mixed>|null} $params Translation options
     * @param callable|null $progressCallback Progress callback (%, batch, result)
     *
     * @return array<string, mixed> Translated data preserving structure
     *
     * @throws \InvalidArgumentException Invalid parameters or reference
     * @throws \RuntimeException API request failure
     */
    public function localizeObject(array $obj, array $params, ?callable $progressCallback = null): array
    {
        if (!isset($params['targetLocale'])) {
            throw new \InvalidArgumentException('Target locale is required');
        }
        
        return $this->localizeRaw($obj, $params, function($progress, $chunk, $processedChunk) use ($progressCallback) {
            if ($progressCallback) {
                $progressCallback($progress, $chunk, $processedChunk);
            }
        });
    }

    /**
     * Localize a single string and return the translated text.
     *
     * Examples:
     *     $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
     *     $engine->localizeText('Hello, world!', ['sourceLocale' => 'en', 'targetLocale' => 'es']);
     *
     *     $engine->localizeText(
     *         'This is a very long text that needs translation...',
     *         ['sourceLocale' => 'en', 'targetLocale' => 'es'],
     *         function (int $progress): void {
     *             echo 'Translation progress: ' . $progress . "%\n";
     *         }
     *     );
     *
     *     $engine->localizeText('Bonjour le monde', ['sourceLocale' => null, 'targetLocale' => 'en']);
     *
     * @param string $text Text to translate
     * @param array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string, mixed>|null} $params Translation options
     * @param callable|null $progressCallback Progress callback (0-100%)
     *
     * @return string Translated text or empty string
     *
     * @throws \InvalidArgumentException Missing or invalid parameters
     * @throws \RuntimeException API request failure
     */
    public function localizeText(string $text, array $params, ?callable $progressCallback = null): string
    {
        if (!isset($params['targetLocale'])) {
            throw new \InvalidArgumentException('Target locale is required');
        }
        
        $response = $this->localizeRaw(['text' => $text], $params, function($progress, $chunk, $processedChunk) use ($progressCallback) {
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
     *     $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
     *     $engine->batchLocalizeText('Hello, world!', [
     *         'sourceLocale' => 'en',
     *         'targetLocales' => ['es', 'fr', 'de'],
     *     ]);
     *
     * @param string $text Text to translate
     * @param array{sourceLocale: string, targetLocales: string[], fast?: bool} $params Batch translation options
     *
     * @return string[] Translated texts in targetLocales order
     *
     * @throws \InvalidArgumentException Missing or invalid parameters
     * @throws \RuntimeException Individual request failure
     */
    public function batchLocalizeText(string $text, array $params): array
    {
        if (!isset($params['sourceLocale'])) {
            throw new \InvalidArgumentException('Source locale is required');
        }

        if (!isset($params['targetLocales']) || !is_array($params['targetLocales'])) {
            throw new \InvalidArgumentException('Target locales must be an array');
        }

        $responses = [];
        foreach ($params['targetLocales'] as $targetLocale) {
            $responses[] = $this->localizeText(
                $text, [
                'sourceLocale' => $params['sourceLocale'],
                'targetLocale' => $targetLocale,
                'fast' => $params['fast'] ?? false
                ]
            );
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
     *     $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
     *     $engine->localizeChat($conversation, ['sourceLocale' => 'en', 'targetLocale' => 'de']);
     *
     * @param array<int, array{name: string, text: string}> $chat Conversation with names and messages
     * @param array{targetLocale: string, sourceLocale?: string|null, fast?: bool, reference?: array<string, mixed>|null} $params Translation options
     * @param callable|null $progressCallback Progress callback (0-100%)
     *
     * @return array<int, array{name: string, text: string}> Translated chat preserving names
     *
     * @throws \InvalidArgumentException Invalid chat entries or parameters
     * @throws \RuntimeException API request failure
     */
    public function localizeChat(array $chat, array $params, ?callable $progressCallback = null): array
    {
        foreach ($chat as $message) {
            if (!isset($message['name']) || !isset($message['text'])) {
                throw new \InvalidArgumentException('Each chat message must have name and text properties');
            }
        }

        $localized = $this->localizeRaw(['chat' => $chat], $params, function($progress, $chunk, $processedChunk) use ($progressCallback) {
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
     *     $engine = new LingoDotDevEngine(['apiKey' => $_ENV['LINGODOTDEV_API_KEY']]);
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
