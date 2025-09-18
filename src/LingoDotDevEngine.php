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
 * @category Localization
 * @package  Lingodotdev\Sdk
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */
class LingoDotDevEngine
{
    /**
     * Configuration options for the Engine
     *
     * @var array
     */
    protected $config;

    /**
     * HTTP client for API requests
     *
     * @var Client
     */
    private $_httpClient;

    /**
     * Build an engine instance with your API key and optional batch settings.
     *
     * Provide at least ['apiKey' => '...']. You may override:
     * - 'apiUrl' with a valid base URL for the service (defaults to https://engine.lingo.dev).
     * - 'batchSize' with an integer between 1 and 250 to control items sent per request.
     * - 'idealBatchItemSize' with an integer between 1 and 2500 to cap words per request.
     * Invalid values trigger \InvalidArgumentException.
     *
     * @param array $config HTTP client credentials and optional batching overrides.
     *
     * @throws \InvalidArgumentException When the API key is missing or a value fails validation.
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
     * Localize content using the Lingo.dev API
     * 
     * @param array         $payload          The content to be localized
     * @param array         $params           Localization parameters including source/target locales and fast mode option
     * @param callable|null $progressCallback Optional callback function to report progress (0-100)
     * 
     * @return   array Localized content
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
     * Localize a single chunk of content
     * 
     * @param string|null $sourceLocale Source locale
     * @param string      $targetLocale Target locale
     * @param array       $payload      Payload containing the chunk to be localized
     * @param string      $workflowId   Workflow ID
     * @param bool        $fast         Whether to use fast mode
     * 
     * @return array Localized chunk
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
     * Extract payload chunks based on the ideal chunk size
     * 
     * @param array $payload The payload to be chunked
     * 
     * @return array An array of payload chunks
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
     * Count words in a record or array
     * 
     * @param mixed $payload The payload to count words in
     * 
     * @return int The total number of words
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
     * Generate a unique ID
     * 
     * @return string Unique ID
     */
    private function _createId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Localize every string in a nested array while keeping the array structure.
     *
     * Require $params['targetLocale'] with the desired language code. Optionally
     * pass:
     * - 'sourceLocale' with the current language code (string or null).
     * - 'fast' with a boolean forwarded to the API.
     * - 'reference' with an array of supplemental data forwarded unchanged.
     * The optional callback receives (int $progress, array $chunk, array $localizedChunk)
     * for each batch the engine submits.
     *
     * @param array         $obj              Input data whose string entries should be localized.
     * @param array         $params           Request parameters for locales and API options.
     * @param callable|null $progressCallback Progress hook fired per batch.
     *
     * @return array Localized data mirroring the original structure.
     *
     * @throws \InvalidArgumentException When required params or reference data are invalid.
     * @throws \RuntimeException         When the API rejects or fails to process the request.
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
     * Translate a single string and return the localized text.
     *
     * Set $params['targetLocale'] to the destination language code. You may
     * also provide:
     * - 'sourceLocale' with the existing language code (string or null).
     * - 'fast' with a boolean forwarded to the API.
     * - 'reference' with an array of supplemental data forwarded unchanged.
     * The optional callback receives the completion percentage (0-100) for each
     * processed batch.
     *
     * @param string        $text             Source text to localize.
     * @param array         $params           Request parameters for locales and API options.
     * @param callable|null $progressCallback Progress hook fired with an integer percentage.
     *
     * @return string Localized text (empty string if the API omits the field).
     *
     * @throws \InvalidArgumentException When required params are missing or invalid.
     * @throws \RuntimeException         When the API rejects or fails to process the request.
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
     * Translate a string into several languages and return the results in order.
     *
     * Expect $params['sourceLocale'] with the language code of the input text
     * and $params['targetLocales'] with an array of destination language codes.
     * Optional 'fast' flags are forwarded to each {@see localizeText()} call.
     * Any failure from an individual request surfaces immediately.
     *
     * @param string $text   Source text to localize.
     * @param array  $params Request parameters describing the source and target languages.
     *
     * @return array List of localized strings matching the order of target locales.
     *
     * @throws \InvalidArgumentException When required params are missing or invalid.
     * @throws \RuntimeException         When an individual localization request fails.
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
     * Localize a chat transcript while keeping speaker names untouched.
     *
     * Each entry in $chat must provide 'name' and 'text'. Supply
     * $params['targetLocale'] with the destination language code. Optionally
     * pass:
     * - 'sourceLocale' with the current language code (string or null).
     * - 'fast' with a boolean forwarded to the API.
     * - 'reference' with an array of supplemental data forwarded unchanged.
     * The optional callback receives the completion percentage (0-100) for each
     * processed batch.
     *
     * @param array         $chat             Ordered list of chat message arrays with 'name' and 'text'.
     * @param array         $params           Request parameters for locales and API options.
     * @param callable|null $progressCallback Progress hook fired with an integer percentage.
     *
     * @return array Localized chat messages with original names preserved.
     *
     * @throws \InvalidArgumentException When chat entries or params are invalid.
     * @throws \RuntimeException         When the API rejects or fails to process the request.
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
     * Ask the API to identify the locale of the provided text.
     *
     * Whitespace-only strings are rejected. On success the API's 'locale'
     * field is returned directly.
     *
     * @param string $text Text whose locale should be recognised.
     *
     * @return string Locale code provided by the API.
     *
     * @throws \InvalidArgumentException When the input text is blank after trimming.
     * @throws \RuntimeException         When the API response is invalid or the request fails.
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
