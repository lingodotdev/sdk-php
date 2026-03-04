<?php
/**
 * Tests for the LingoDotDevEngine class
 *
 * @category Tests
 * @package  Lingodotdev\Sdk\Tests
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */

namespace LingoDotDev\Sdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LingoDotDev\Sdk\LingoDotDevEngine;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test cases for the LingoDotDevEngine class
 *
 * @category Tests
 * @package  Lingodotdev\Sdk\Tests
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */
class LingoDotDevEngineTest extends TestCase
{
    /**
     * Creates a mock engine with predefined responses
     *
     * @param array $responses Array of mock responses
     *
     * @return LingoDotDevEngine Mocked engine instance
     */
    private function _createMockEngine($responses)
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client([
            'handler' => $handlerStack,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-API-Key' => 'test-api-key'
            ]
        ]);

        $engine = new LingoDotDevEngine([
            'apiKey' => 'test-api-key',
            'engineId' => 'test-engine'
        ]);

        $reflection = new ReflectionClass($engine);
        $property = $reflection->getProperty('_httpClient');
        $property->setAccessible(true);
        $property->setValue($engine, $client);

        return $engine;
    }

    /**
     * Creates a mock engine with history tracking
     *
     * @param array $responses Array of mock responses
     * @param array &$history  Reference to history array for capturing requests
     * @param array $config    Engine config overrides
     *
     * @return LingoDotDevEngine Mocked engine instance
     */
    private function _createMockEngineWithHistory($responses, &$history, $config = [])
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(\GuzzleHttp\Middleware::history($history));
        $engineConfig = array_merge([
            'apiKey' => 'test-api-key',
            'engineId' => 'test-engine'
        ], $config);

        $client = new Client([
            'handler' => $handlerStack,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-API-Key' => $engineConfig['apiKey']
            ]
        ]);

        $engine = new LingoDotDevEngine($engineConfig);

        $reflection = new ReflectionClass($engine);
        $property = $reflection->getProperty('_httpClient');
        $property->setAccessible(true);
        $property->setValue($engine, $client);

        return $engine;
    }

    /**
     * Tests constructor with valid configuration
     *
     * @return void
     */
    public function testConstructorWithValidConfig()
    {
        $engine = new LingoDotDevEngine([
            'apiKey' => 'test-api-key',
            'engineId' => 'test-engine'
        ]);
        $this->assertInstanceOf(LingoDotDevEngine::class, $engine);
    }

    /**
     * Tests constructor requires engineId
     *
     * @return void
     */
    public function testConstructorRequiresEngineId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Engine ID is required');
        new LingoDotDevEngine(['apiKey' => 'test-api-key']);
    }

    /**
     * Tests constructor requires apiKey
     *
     * @return void
     */
    public function testConstructorRequiresApiKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        new LingoDotDevEngine([]);
    }

    /**
     * Tests the localizeText method
     *
     * @return void
     */
    public function testLocalizeText()
    {
        $engine = $this->_createMockEngine(
            [
            new Response(
                200, [], json_encode(
                    [
                    'data' => ['text' => 'Hola, mundo!']
                    ]
                )
            )
            ]
        );

        $result = $engine->localizeText(
            'Hello, world!', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
            ]
        );

        $this->assertEquals('Hola, mundo!', $result);
    }

    /**
     * Tests the localizeObject method
     *
     * @return void
     */
    public function testLocalizeObject()
    {
        $engine = $this->_createMockEngine(
            [
            new Response(
                200, [], json_encode(
                    [
                    'data' => [
                    'greeting' => 'Hola',
                    'farewell' => 'Adiós'
                    ]
                    ]
                )
            )
            ]
        );

        $result = $engine->localizeObject(
            [
            'greeting' => 'Hello',
            'farewell' => 'Goodbye'
            ], [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
            ]
        );

        $this->assertEquals(
            [
            'greeting' => 'Hola',
            'farewell' => 'Adiós'
            ], $result
        );
    }

    /**
     * Tests the batchLocalizeText method
     *
     * @return void
     */
    public function testBatchLocalizeText()
    {
        $engine = $this->_createMockEngine(
            [
            new Response(
                200, [], json_encode(
                    [
                    'data' => ['text' => 'Hola, mundo!']
                    ]
                )
            ),
            new Response(
                200, [], json_encode(
                    [
                    'data' => ['text' => 'Bonjour, monde!']
                    ]
                )
            )
            ]
        );

        $result = $engine->batchLocalizeText(
            'Hello, world!', [
            'sourceLocale' => 'en',
            'targetLocales' => ['es', 'fr']
            ]
        );

        $this->assertEquals(['Hola, mundo!', 'Bonjour, monde!'], $result);
    }

    /**
     * Tests the localizeChat method
     *
     * @return void
     */
    public function testLocalizeChat()
    {
        $engine = $this->_createMockEngine(
            [
            new Response(
                200, [], json_encode(
                    [
                    'data' => [
                        'chat' => [
                            ['text' => '¡Hola, cómo estás?'],
                            ['text' => '¡Estoy bien, gracias!']
                        ]
                    ]
                    ]
                )
            )
            ]
        );

        $chat = [
            ['name' => 'Alice', 'text' => 'Hello, how are you?'],
            ['name' => 'Bob', 'text' => 'I am fine, thank you!']
        ];

        $result = $engine->localizeChat(
            $chat, [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
            ]
        );

        $expected = [
            ['name' => 'Alice', 'text' => '¡Hola, cómo estás?'],
            ['name' => 'Bob', 'text' => '¡Estoy bien, gracias!']
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the recognizeLocale method
     *
     * @return void
     */
    public function testRecognizeLocale()
    {
        $engine = $this->_createMockEngine(
            [
            new Response(
                200, [], json_encode(
                    [
                    'locale' => 'fr'
                    ]
                )
            )
            ]
        );

        $result = $engine->recognizeLocale('Bonjour le monde');
        $this->assertEquals('fr', $result);
    }

    /**
     * Tests error handling in the SDK
     *
     * @return void
     */
    public function testErrorHandling()
    {
        $engine = $this->_createMockEngine(
            [
            new Response(
                400, [], json_encode(
                    [
                    'error' => 'Invalid request'
                    ]
                )
            )
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $engine->localizeText(
            'Hello, world!', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
            ]
        );
    }

    /**
     * Tests the reference parameter handling
     *
     * @return void
     */
    public function testReferenceParameterHandling()
    {
        $history = [];
        $engine = $this->_createMockEngineWithHistory(
            [new Response(200, [], json_encode(['data' => ['text' => 'Hola, mundo!']]))],
            $history
        );

        $engine->localizeText('Hello, world!', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
        ]);

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $requestBody = json_decode($request->getBody()->getContents(), true);

        // vNext omits reference when not provided
        $this->assertArrayNotHasKey('reference', $requestBody);

        $this->assertArrayHasKey('params', $requestBody);
        $this->assertArrayHasKey('sourceLocale', $requestBody);
        $this->assertArrayHasKey('targetLocale', $requestBody);
        $this->assertArrayHasKey('data', $requestBody);
        $this->assertEquals('en', $requestBody['sourceLocale']);
        $this->assertEquals('es', $requestBody['targetLocale']);
        $this->assertEquals(['text' => 'Hello, world!'], $requestBody['data']);
    }

    /**
     * Tests the progress callback functionality
     *
     * @return void
     */
    public function testProgressCallback()
    {
        $engine = $this->_createMockEngine(
            [
            new Response(
                200, [], json_encode(
                    [
                    'data' => ['text' => 'Hola, mundo!']
                    ]
                )
            )
            ]
        );

        $progressCalled = false;
        $progressValue = 0;

        $engine->localizeText(
            'Hello, world!', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
            ], function ($progress) use (&$progressCalled, &$progressValue) {
                $progressCalled = true;
                $progressValue = $progress;
            }
        );

        $this->assertTrue($progressCalled);
        $this->assertEquals(100, $progressValue);
    }

    /**
     * Tests default apiUrl is api.lingo.dev
     *
     * @return void
     */
    public function testConfigDefaultApiUrl()
    {
        $engine = new LingoDotDevEngine([
            'apiKey' => 'test-api-key',
            'engineId' => 'my-engine-id'
        ]);

        $reflection = new ReflectionClass($engine);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $config = $property->getValue($engine);

        $this->assertEquals('https://api.lingo.dev', $config['apiUrl']);
        $this->assertEquals('my-engine-id', $config['engineId']);
    }

    /**
     * Tests that explicit apiUrl is preserved even with engineId
     *
     * @return void
     */
    public function testConfigExplicitApiUrl()
    {
        $engine = new LingoDotDevEngine([
            'apiKey' => 'test-api-key',
            'engineId' => 'my-engine-id',
            'apiUrl' => 'https://custom.api.com'
        ]);

        $reflection = new ReflectionClass($engine);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $config = $property->getValue($engine);

        $this->assertEquals('https://custom.api.com', $config['apiUrl']);
    }

    /**
     * Tests that X-API-Key header is used
     *
     * @return void
     */
    public function testXApiKeyHeader()
    {
        $history = [];
        $engine = $this->_createMockEngineWithHistory(
            [new Response(200, [], json_encode(['data' => ['text' => 'Hola']]))],
            $history,
            ['engineId' => 'my-engine']
        );

        $engine->localizeText('Hello', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
        ]);

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertEquals('test-api-key', $request->getHeaderLine('X-API-Key'));
        $this->assertEmpty($request->getHeaderLine('Authorization'));
    }

    /**
     * Tests localize chunk URL and body format
     *
     * @return void
     */
    public function testLocalizeChunkUrlAndBody()
    {
        $history = [];
        $engine = $this->_createMockEngineWithHistory(
            [new Response(200, [], json_encode(['data' => ['text' => 'Hola']]))],
            $history,
            ['engineId' => 'my-engine']
        );

        $engine->localizeText('Hello', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es',
            'fast' => true
        ]);

        $this->assertCount(1, $history);
        $request = $history[0]['request'];

        // Check URL
        $this->assertStringContainsString('/process/my-engine/localize', $request->getUri()->getPath());

        // Check body format
        $body = json_decode($request->getBody()->getContents(), true);
        $this->assertEquals(['fast' => true], $body['params']);
        $this->assertEquals('en', $body['sourceLocale']);
        $this->assertEquals('es', $body['targetLocale']);
        $this->assertEquals(['text' => 'Hello'], $body['data']);
        $this->assertArrayHasKey('sessionId', $body);
        $this->assertNotEmpty($body['sessionId']);

        // vNext should NOT have workflowId or locale object
        $this->assertArrayNotHasKey('workflowId', $body['params']);
        $this->assertArrayNotHasKey('locale', $body);
    }

    /**
     * Tests that sessionId is consistent across multiple requests
     *
     * @return void
     */
    public function testSessionIdConsistentAcrossRequests()
    {
        $history = [];
        $engine = $this->_createMockEngineWithHistory(
            [
                new Response(200, [], json_encode(['data' => ['text' => 'Hola']])),
                new Response(200, [], json_encode(['data' => ['text' => 'Mundo']]))
            ],
            $history,
            ['engineId' => 'my-engine']
        );

        $engine->localizeText('Hello', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
        ]);
        $engine->localizeText('World', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
        ]);

        $body1 = json_decode($history[0]['request']->getBody()->getContents(), true);
        $body2 = json_decode($history[1]['request']->getBody()->getContents(), true);
        $this->assertEquals($body1['sessionId'], $body2['sessionId']);
    }

    /**
     * Tests that reference is omitted when not provided
     *
     * @return void
     */
    public function testOmitsReferenceWhenNotProvided()
    {
        $history = [];
        $engine = $this->_createMockEngineWithHistory(
            [new Response(200, [], json_encode(['data' => ['text' => 'Hola']]))],
            $history,
            ['engineId' => 'my-engine']
        );

        $engine->localizeText('Hello', [
            'sourceLocale' => 'en',
            'targetLocale' => 'es'
        ]);

        $body = json_decode($history[0]['request']->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('reference', $body);
    }

    /**
     * Tests that reference is included when provided
     *
     * @return void
     */
    public function testIncludesReferenceWhenProvided()
    {
        $history = [];
        $engine = $this->_createMockEngineWithHistory(
            [new Response(200, [], json_encode(['data' => ['greeting' => 'Hola']]))],
            $history,
            ['engineId' => 'my-engine']
        );

        $engine->localizeObject(
            ['greeting' => 'Hello'],
            [
                'sourceLocale' => 'en',
                'targetLocale' => 'es',
                'reference' => ['fr' => ['greeting' => 'Bonjour']]
            ]
        );

        $body = json_decode($history[0]['request']->getBody()->getContents(), true);
        $this->assertArrayHasKey('reference', $body);
        $this->assertEquals(['fr' => ['greeting' => 'Bonjour']], $body['reference']);
    }

    /**
     * Tests recognizeLocale URL
     *
     * @return void
     */
    public function testRecognizeLocaleUrl()
    {
        $history = [];
        $engine = $this->_createMockEngineWithHistory(
            [new Response(200, [], json_encode(['locale' => 'fr']))],
            $history,
            ['engineId' => 'my-engine']
        );

        $result = $engine->recognizeLocale('Bonjour le monde');

        $this->assertEquals('fr', $result);
        $this->assertStringContainsString('/process/recognize', $history[0]['request']->getUri()->getPath());
    }

}
