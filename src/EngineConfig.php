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

/**
 * Configuration for LingoDotDevEngine initialization.
 *
 * @category Localization
 * @package  Lingodotdev\Sdk
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */
class EngineConfig
{
    /**
     * Your Lingo.dev API token
     */
    public string $apiKey;

    /**
     * API base URL (default: https://engine.lingo.dev)
     */
    public string $apiUrl = 'https://engine.lingo.dev';

    /**
     * Maximum records per request (1-250, default: 25)
     */
    public int $batchSize = 25;

    /**
     * Maximum words per request (1-2500, default: 250)
     */
    public int $idealBatchItemSize = 250;

    /**
     * Create configuration with API key.
     *
     * @param string $apiKey Your Lingo.dev API token
     */
    public static function create(string $apiKey): self
    {
        $instance = new self();
        $instance->apiKey = $apiKey;
        return $instance;
    }

    /**
     * Set custom API URL.
     *
     * @param string $url API endpoint URL
     */
    public function withApiUrl(string $url): self
    {
        $this->apiUrl = $url;
        return $this;
    }

    /**
     * Set batch size limit.
     *
     * @param int $size Records per request (1-250)
     */
    public function withBatchSize(int $size): self
    {
        $this->batchSize = $size;
        return $this;
    }

    /**
     * Set ideal batch item size.
     *
     * @param int $size Max words per request (1-2500)
     */
    public function withIdealBatchItemSize(int $size): self
    {
        $this->idealBatchItemSize = $size;
        return $this;
    }
}