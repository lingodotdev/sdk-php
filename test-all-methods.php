<?php
/**
 * Test script for all API methods in the PHP SDK
 *
 * This script tests all available methods in the PHP SDK with real API calls
 * to ensure they work correctly.
 *
 * Usage: php test-all-methods.php <api_key> <engine_id>
 */

require "vendor/autoload.php";

use LingoDotDev\Sdk\LingoDotDevEngine;
use GuzzleHttp\Exception\RequestException;

$apiKey = $argv[1] ?? null;
$engineId = $argv[2] ?? null;

if (!$apiKey || !$engineId) {
    echo "Usage: php test-all-methods.php <api_key> <engine_id>\n";
    exit(1);
}

$engine = new LingoDotDevEngine([
    "apiKey" => $apiKey,
    "engineId" => $engineId,
]);

$passed = 0;
$failed = 0;

function runTest($name, $callback) {
    global $passed, $failed;
    echo "\n=== Testing $name ===\n";
    try {
        $result = $callback();
        echo "✅ Test passed!\n";
        echo "Result: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        $passed++;
        return true;
    } catch (\Exception $e) {
        echo "❌ Test failed!\n";
        echo "Error: " . $e->getMessage() . "\n";

        if ($e instanceof RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            echo "Status Code: " . $response->getStatusCode() . "\n";
            echo "Response Body: " . $response->getBody() . "\n";
        }
        $failed++;
        return false;
    }
}

// 1. localizeText
runTest("localizeText", function() use ($engine) {
    return $engine->localizeText("Hello, this is my first localization with Lingo.dev!", [
        "sourceLocale" => "en",
        "targetLocale" => "es",
    ]);
});

// 2. localizeText with fast mode
runTest("localizeText (fast mode)", function() use ($engine) {
    return $engine->localizeText("The quick brown fox jumps over the lazy dog.", [
        "sourceLocale" => "en",
        "targetLocale" => "fr",
        "fast" => true,
    ]);
});

// 3. localizeObject
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

// 4. localizeChat
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

// 5. batchLocalizeText
runTest("batchLocalizeText", function() use ($engine) {
    return $engine->batchLocalizeText("Hello, world!", [
        "sourceLocale" => "en",
        "targetLocales" => ["es", "fr", "de"],
    ]);
});

// 6. recognizeLocale
runTest("recognizeLocale", function() use ($engine) {
    return $engine->recognizeLocale("Bonjour le monde");
});

// 7. localizeObject with reference
runTest("localizeObject with reference", function() use ($engine) {
    return $engine->localizeObject([
        "greeting" => "Hello",
        "farewell" => "Goodbye",
    ], [
        "sourceLocale" => "en",
        "targetLocale" => "es",
        "reference" => [
            "fr" => [
                "greeting" => "Bonjour",
                "farewell" => "Au revoir"
            ]
        ],
    ]);
});

// 8. Progress callback
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

echo "\n=== All tests completed: $passed passed, $failed failed ===\n";
exit($failed > 0 ? 1 : 0);
