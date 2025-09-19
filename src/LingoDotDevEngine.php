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
 * @example Basic setup
 * ```php
 * <?php
 * 
 * require 'vendor/autoload.php';
 * 
 * use LingoDotDev\Sdk\LingoDotDevEngine;
 * 
 * $engine = new LingoDotDevEngine([
 *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
 * ]);
 * ```
 *
 * @example Laravel integration
 * ```php
 * <?php
 * 
 * namespace App\Http\Controllers;
 * 
 * use Illuminate\Http\Request;
 * use LingoDotDev\Sdk\LingoDotDevEngine;
 * 
 * class TranslationController extends Controller
 * {
 *     private $engine;
 * 
 *     public function __construct()
 *     {
 *         $this->engine = new LingoDotDevEngine([
 *             'apiKey' => config('services.lingodotdev.api_key'),
 *         ]);
 *     }
 * 
 *     public function translateMessage(Request $request)
 *     {
 *         $translated = $this->engine->localizeText($request->message, [
 *             'sourceLocale' => $request->source_locale,
 *             'targetLocale' => $request->target_locale,
 *         ]);
 * 
 *         return response()->json(['translated' => $translated]);
 *     }
 * }
 * ```
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
     * @var array<string, mixed>
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
     * @param array<string, mixed> $config Configuration options:
     *                                      - 'apiKey' (string, required): Your API token
     *                                      - 'apiUrl' (string): API base URL (default: https://engine.lingo.dev)
     *                                      - 'batchSize' (int): Records per request, 1-250 (default: 25)
     *                                      - 'idealBatchItemSize' (int): Max words per request, 1-2500 (default: 250)
     *
     * @example Configuration
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     *     'batchSize' => 100,
     *     'idealBatchItemSize' => 1000,
     * ]);
     * 
     * $result = $engine->localizeText('Configuration test', [
     *     'sourceLocale' => 'en',
     *     'targetLocale' => 'es',
     * ]);
     * echo $result;
     * // Output: Prueba de configuración
     * ```
     *
     * @throws \InvalidArgumentException When API key is missing or values fail validation
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
     * @param array<string, mixed>         $payload          Content to translate, structured as key-value pairs
     * @param array<string, mixed>         $params           Translation configuration options
     * @param null|callable(int, mixed, mixed): void $progressCallback Callback invoked with completion percentage (0-100), current chunk, and processed chunk
     *
     * @return array<string, mixed> Translated content maintaining original structure
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
     * @param string|null          $sourceLocale Language code of the original text (e.g., 'en', 'es'), null for auto-detection
     * @param string               $targetLocale Language code to translate into (e.g., 'fr', 'de')
     * @param array<string, mixed> $payload      Content chunk with optional reference data for context
     * @param string               $workflowId   Unique identifier for tracking related translation requests
     * @param bool                 $fast         Enable faster translation at potential quality tradeoff
     *
     * @return array<string, mixed> Translated chunk maintaining original structure
     *
     * @throws \InvalidArgumentException When reference is not an array
     * @throws \RuntimeException When API request fails
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
     * @param array<string, mixed> $obj              Nested data structure containing text to translate
     * @param array<string, mixed> $params           Parameters:
     *                                                - 'targetLocale' (string, required): Language code to translate into (e.g., 'es', 'fr')
     *                                                - 'sourceLocale' (string|null): Language code of original text, null for auto-detection
     *                                                - 'fast' (bool): Trade translation quality for speed
     *                                                - 'reference' (array): Context or glossary terms to guide translation
     * @param null|callable(int, mixed, mixed): void $progressCallback Invoked per batch with (percentage complete, current batch, translated batch)
     *
     * @return array<string, mixed> Translated data preserving original structure and non-text values
     *
     * @example Object translation
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $content = [
     *     'greeting' => 'Hello',
     *     'farewell' => 'Goodbye',
     *     'messages' => [
     *         'welcome' => 'Welcome to our service',
     *         'thanks' => 'Thank you for your business'
     *     ]
     * ];
     * 
     * $translated = $engine->localizeObject($content, [
     *     'sourceLocale' => 'en',
     *     'targetLocale' => 'fr',
     * ]);
     * print_r($translated);
     * // Output: Array with French translations maintaining structure
     * ```
     *
     * @throws \InvalidArgumentException When required params or reference data are invalid
     * @throws \RuntimeException When API rejects or fails to process the request
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
     * @param string                          $text             Text content to translate
     * @param array<string, mixed>            $params           Parameters:
     *                                                          - 'targetLocale' (string, required): Language code to translate into (e.g., 'es', 'fr')
     *                                                          - 'sourceLocale' (string|null): Language code of original text, null for auto-detection
     *                                                          - 'fast' (bool): Prioritize speed over translation quality
     *                                                          - 'reference' (array): Context, terminology, or style guidelines for translation
     * @param null|callable(int): void $progressCallback Called with completion percentage (0-100) during processing
     *
     * @return string Translated text, or empty string if translation unavailable
     *
     * @example Text translation
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $result = $engine->localizeText('Hello, world!', [
     *     'sourceLocale' => 'en',
     *     'targetLocale' => 'es',
     * ]);
     * echo $result;
     * // Output: ¡Hola, mundo!
     * ```
     *
     * @example Progress tracking
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $largeText = 'This is a very long text that needs translation...';
     * 
     * $result = $engine->localizeText($largeText, [
     *     'sourceLocale' => 'en',
     *     'targetLocale' => 'es',
     * ], function ($progress) {
     *     echo "Translation progress: $progress%\n";
     * });
     * // Output:
     * // Translation progress: 25%
     * // Translation progress: 50%
     * // Translation progress: 75%
     * // Translation progress: 100%
     * ```
     *
     * @example Translation parameters
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $result = $engine->localizeText('Hello world', [
     *     'sourceLocale' => 'en',
     *     'targetLocale' => 'es',
     *     'fast' => true,
     * ]);
     * echo $result;
     * // Output: Hola mundo
     * ```
     *
     * @example Automatic language detection
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $result = $engine->localizeText('Bonjour le monde', [
     *     'sourceLocale' => null,
     *     'targetLocale' => 'en',
     * ]);
     * echo $result;
     * // Output: Hello world
     * ```
     *
     * @example Error handling
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * try {
     *     $result = $engine->localizeText('Hello', [
     *         'sourceLocale' => 'en',
     *         'targetLocale' => 'es',
     *     ]);
     *     echo $result;
     * } catch (Exception $e) {
     *     error_log('Translation failed: ' . $e->getMessage());
     * }
     * ```
     *
     * @throws \InvalidArgumentException When required params are missing or invalid
     * @throws \RuntimeException When API rejects or fails to process the request
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
     * @param string               $text   Text content to translate into multiple languages
     * @param array<string, mixed> $params Parameters:
     *                                     - 'sourceLocale' (string, required): Language code of the original text (e.g., 'en')
     *                                     - 'targetLocales' (string[], required): Array of language codes to translate into (e.g., ['es', 'fr', 'de'])
     *                                     - 'fast' (bool): Apply speed optimization to all translations
     *
     * @return string[] Array of translated texts in same order as targetLocales parameter
     *
     * @example Batch translation to multiple languages
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $results = $engine->batchLocalizeText('Hello, world!', [
     *     'sourceLocale' => 'en',
     *     'targetLocales' => ['es', 'fr', 'de'],
     * ]);
     * print_r($results);
     * // Output: ["¡Hola, mundo!", "Bonjour le monde!", "Hallo, Welt!"]
     * ```
     *
     * @throws \InvalidArgumentException When required params are missing or invalid
     * @throws \RuntimeException When an individual localization request fails
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
     * @param array<int, array{name: string, text: string}> $chat             Conversation history with speaker names and their messages
     * @param array<string, mixed>                           $params           Parameters:
     *                                                                          - 'targetLocale' (string, required): Language code to translate messages into (e.g., 'es', 'fr')
     *                                                                          - 'sourceLocale' (string|null): Language of original messages, null for auto-detection
     *                                                                          - 'fast' (bool): Optimize for speed over translation quality
     *                                                                          - 'reference' (array): Conversation context or domain-specific terminology
     * @param null|callable(int): void                       $progressCallback Called with completion percentage (0-100) during processing
     *
     * @return array<int, array{name: string, text: string}> Translated messages keeping original speaker names unchanged
     *
     * @example Chat translation
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $conversation = [
     *     ['name' => 'Alice', 'text' => 'Hello, how are you?'],
     *     ['name' => 'Bob', 'text' => 'I am fine, thank you!'],
     *     ['name' => 'Alice', 'text' => 'What are you doing today?']
     * ];
     * 
     * $translated = $engine->localizeChat($conversation, [
     *     'sourceLocale' => 'en',
     *     'targetLocale' => 'de',
     * ]);
     * 
     * foreach ($translated as $message) {
     *     echo $message['name'] . ': ' . $message['text'] . "\n";
     * }
     * // Output:
     * // Alice: Hallo, wie geht es dir?
     * // Bob: Mir geht es gut, danke!
     * // Alice: Was machst du heute?
     * ```
     *
     * @throws \InvalidArgumentException When chat entries or params are invalid
     * @throws \RuntimeException When API rejects or fails to process the request
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
     * @param string $text Sample text for language detection (longer text improves accuracy)
     *
     * @return string ISO language code detected by the API (e.g., 'en', 'es', 'zh')
     *
     * @example Language detection
     * ```php
     * <?php
     * 
     * require 'vendor/autoload.php';
     * 
     * use LingoDotDev\Sdk\LingoDotDevEngine;
     * 
     * $engine = new LingoDotDevEngine([
     *     'apiKey' => $_ENV['LINGODOTDEV_API_KEY'],
     * ]);
     * 
     * $locale = $engine->recognizeLocale('Bonjour le monde');
     * echo $locale;
     * // Output: fr
     * ```
     *
     * @throws \InvalidArgumentException When input text is blank after trimming
     * @throws \RuntimeException When API response is invalid or request fails
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
