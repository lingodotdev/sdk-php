<?php
/**
 * Test script for all API methods in the PHP SDK
 * 
 * This script tests all available methods in the PHP SDK with real API calls
 * to ensure they work correctly with our fixed implementation.
 * 
 * Usage: php test-all-methods.php <api_key>
 */

require "vendor/autoload.php";

use LingoDotDev\Sdk\LingoDotDevEngine;
use GuzzleHttp\Exception\RequestException;

$apiKey = $argv[1] ?? null;

if (!$apiKey) {
    echo "Error: API key is required. Pass it as a command-line argument.\n";
    exit(1);
}

$engine = new LingoDotDevEngine([
    "apiKey" => $apiKey,
]);

/**
 * Execute a CLI test callback, reporting success or failure to stdout.
 *
 * Prints the section header, runs the callback, dumps the JSON-encoded result
 * when successful, or surfaces exception details (including HTTP responses).
 *
 * @param string   $name     Human-readable test name displayed in logs.
 * @param callable $callback Zero-argument function returning the test result.
 *
 * @return bool True on success, false on failure.
 */
function runTest($name, $callback) {
    echo "\n=== Testing $name ===\n";
    try {
        $result = $callback();
        echo "✅ Test passed!\n";
        echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        return true;
    } catch (\Exception $e) {
        echo "❌ Test failed!\n";
        echo "Error: " . $e->getMessage() . "\n";
        
        if ($e instanceof RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            echo "Status Code: " . $response->getStatusCode() . "\n";
            echo "Response Body: " . $response->getBody() . "\n";
        }
        return false;
    }
}

runTest("localizeText", function() use ($engine) {
    return $engine->localizeText("Hello, this is my first localization with Lingo.dev!", [
        "sourceLocale" => "en",
        "targetLocale" => "es",
    ]);
});

runTest("localizeObject", function() use ($engine) {
    return $engine->localizeObject([
        "greeting" => "Hello",
        "farewell" => "Goodbye",
        "messages" => [
            "welcome" => "Welcome to our service",
            "thanks" => "Thank you for your business"
        ]
    ], [
        "sourceLocale" => "en",
        "targetLocale" => "fr",
    ]);
});

runTest("localizeChat", function() use ($engine) {
    return $engine->localizeChat([
        ["name" => "Alice", "text" => "Hello, how are you?"],
        ["name" => "Bob", "text" => "I am fine, thank you!"],
        ["name" => "Alice", "text" => "What are you doing today?"]
    ], [
        "sourceLocale" => "en",
        "targetLocale" => "de",
    ]);
});

runTest("batchLocalizeText", function() use ($engine) {
    return $engine->batchLocalizeText("Hello, world!", [
        "sourceLocale" => "en",
        "targetLocales" => ["es", "fr", "de"],
    ]);
});

runTest("recognizeLocale", function() use ($engine) {
    return $engine->recognizeLocale("Bonjour le monde");
});

runTest("Progress Callback", function() use ($engine) {
    $progressCalled = false;
    $progressValue = 0;
    
    $result = $engine->localizeText("Hello, this is a test with progress callback!", [
        "sourceLocale" => "en",
        "targetLocale" => "es",
    ], function ($progress) use (&$progressCalled, &$progressValue) {
        $progressCalled = true;
        $progressValue = $progress;
        echo "Progress: $progress%\n";
    });
    
    if (!$progressCalled) {
        throw new \Exception("Progress callback was not called");
    }
    
    return [
        "result" => $result,
        "progressCalled" => $progressCalled,
        "progressValue" => $progressValue
    ];
});

echo "\n=== All tests completed ===\n";
